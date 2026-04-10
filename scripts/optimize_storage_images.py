#!/usr/bin/env python3
"""
Batch-optimize raster images under storage/ in place (same path and filename).
Matches upload pipeline targets: max long edge 1920px, JPEG quality 86.
Requires: pip install Pillow
"""
from __future__ import annotations

import io
import os
import sys
from pathlib import Path

from PIL import Image, ImageOps

# Aligned with api/v1/index.php mci_business_upload_optimize call site
MAX_EDGE = 1920
JPEG_QUALITY = 86
WEBP_QUALITY = 86
PNG_COMPRESS = 9

IMAGE_SUFFIXES = {".jpg", ".jpeg", ".png", ".webp", ".gif"}


def resize_if_needed(im: Image.Image) -> Image.Image:
    w, h = im.size
    long = max(w, h)
    if long <= MAX_EDGE:
        return im
    ratio = MAX_EDGE / long
    nw = max(1, int(round(w * ratio)))
    nh = max(1, int(round(h * ratio)))
    return im.resize((nw, nh), Image.Resampling.LANCZOS)


def optimize_jpeg(im: Image.Image) -> bytes | None:
    if im.mode in ("RGBA", "P"):
        im = im.convert("RGB")
    elif im.mode != "RGB":
        im = im.convert("RGB")
    buf = io.BytesIO()
    im.save(
        buf,
        format="JPEG",
        quality=JPEG_QUALITY,
        optimize=True,
        progressive=True,
        subsampling=2,
    )
    return buf.getvalue()


def optimize_png(im: Image.Image) -> bytes | None:
    if im.mode == "P" and "transparency" in im.info:
        im = im.convert("RGBA")
    buf = io.BytesIO()
    im.save(buf, format="PNG", optimize=True, compress_level=PNG_COMPRESS)
    return buf.getvalue()


def optimize_webp(im: Image.Image) -> bytes | None:
    buf = io.BytesIO()
    lossless = im.mode in ("RGBA", "LA") or (
        im.mode == "P" and "transparency" in im.info
    )
    if lossless:
        im = im.convert("RGBA")
        im.save(buf, format="WEBP", lossless=True, method=6)
    else:
        if im.mode != "RGB":
            im = im.convert("RGB")
        im.save(buf, format="WEBP", quality=WEBP_QUALITY, method=6)
    return buf.getvalue()


def optimize_gif(im: Image.Image) -> bytes | None:
    buf = io.BytesIO()
    frame = im.copy()
    if frame.mode not in ("P", "RGB", "RGBA", "L"):
        frame = frame.convert("RGBA")
    frame.save(buf, format="GIF", save_all=False, optimize=True)
    return buf.getvalue()


def process_file(path: Path) -> tuple[str, int, int]:
    """
    Returns (status, old_bytes, new_bytes).
    status: 'skip' | 'ok' | 'err' | 'unchanged'
    """
    try:
        old_size = path.stat().st_size
    except OSError as e:
        return ("err", 0, 0)

    try:
        with Image.open(path) as im:
            if path.suffix.lower() == ".gif" and getattr(im, "n_frames", 1) > 1:
                return ("skip", old_size, old_size)

            im = ImageOps.exif_transpose(im)
            resized = resize_if_needed(im)
            ext = path.suffix.lower()

            if ext in (".jpg", ".jpeg"):
                data = optimize_jpeg(resized)
            elif ext == ".png":
                data = optimize_png(resized)
            elif ext == ".webp":
                data = optimize_webp(resized)
            elif ext == ".gif":
                data = optimize_gif(resized)
            else:
                return ("skip", old_size, old_size)

            if data is None:
                return ("skip", old_size, old_size)

            new_size = len(data)
            # Avoid replacing with larger file unless dimensions were reduced
            dim_reduced = resized.size != im.size
            if new_size >= old_size and not dim_reduced:
                return ("unchanged", old_size, old_size)

            tmp = path.with_suffix(path.suffix + ".opt.tmp")
            tmp.write_bytes(data)
            os.replace(tmp, path)
            return ("ok", old_size, new_size)
    except Exception:
        return ("err", old_size, old_size)


def main() -> int:
    root = Path(__file__).resolve().parent.parent / "storage"
    if not root.is_dir():
        print(f"Missing directory: {root}", file=sys.stderr)
        return 1

    stats = {"ok": 0, "unchanged": 0, "skip": 0, "err": 0}
    saved = 0
    files: list[Path] = []
    for dirpath, _dirnames, filenames in os.walk(root):
        for name in filenames:
            p = Path(dirpath) / name
            if p.suffix.lower() in IMAGE_SUFFIXES:
                files.append(p)

    files.sort()
    for path in files:
        status, old_b, new_b = process_file(path)
        stats[status] = stats.get(status, 0) + 1
        if status == "ok":
            saved += old_b - new_b
            rel = path.relative_to(root)
            print(f"ok  {rel}  {old_b} -> {new_b} bytes")
        elif status == "err":
            print(f"ERR {path.relative_to(root)}", file=sys.stderr)

    print(
        f"\nDone. ok={stats['ok']} unchanged={stats['unchanged']} "
        f"skip={stats['skip']} err={stats['err']}  saved ~{saved / 1024 / 1024:.2f} MiB"
    )
    return 0 if stats["err"] == 0 else 2


if __name__ == "__main__":
    raise SystemExit(main())
