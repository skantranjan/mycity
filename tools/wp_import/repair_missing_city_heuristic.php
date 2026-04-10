<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
require_once __DIR__ . '/gaddress_parse.php';
mci_load_dotenv();

function env_req(string $k): string { $v=getenv($k); if(!is_string($v)||trim($v)==='') throw new RuntimeException("Missing {$k}"); return trim($v); }
function env_opt(string $k, ?string $d=null): ?string { $v=getenv($k); return (is_string($v)&&trim($v)!=='')?trim($v):$d; }
function pdo_mysql(string $h,string $db,string $u,string $p,?string $port=null): PDO {
  $pp = ($port!==null&&$port!=='')?';port='.(int)$port:'';
  return new PDO("mysql:host={$h}{$pp};dbname={$db};charset=utf8mb4",$u,$p,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function detect_wp_prefix(PDO $wp): string {
  $cfg=env_opt('WP_TABLE_PREFIX'); if($cfg!==null) return $cfg;
  foreach($wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r){$t=(string)($r[0]??''); if(str_ends_with($t,'posts')) return substr($t,0,-5);}
  throw new RuntimeException('WP prefix not found');
}
function is_missing_city(?string $c): bool {
  $v = strtolower(trim((string)$c));
  return $v === '' || $v === 'unknown';
}
try {
  $mci = pdo_mysql(env_req('MCI_DB_HOST'),env_req('MCI_DB_NAME'),env_req('MCI_DB_USER'),env_req('MCI_DB_PASS'),env_opt('MCI_DB_PORT'));
  $wp  = pdo_mysql(env_req('WP_DB_HOST'),env_req('WP_DB_NAME'),env_req('WP_DB_USER'),env_req('WP_DB_PASS'),env_opt('WP_DB_PORT'));
  $pfx = detect_wp_prefix($wp);

  $rows = $mci->query("
    SELECT m.source_id AS wp_post_id, m.target_id AS group_id, b.id AS branch_id, b.city
    FROM mci_wp_import_map m
    INNER JOIN mci_business_branches b ON b.business_group_id = m.target_id AND b.is_primary = 1
    WHERE m.source_type='wp_post' AND m.target_type='mci_business_group'
      AND (b.city IS NULL OR TRIM(b.city)='' OR LOWER(TRIM(b.city))='unknown')
  ")->fetchAll();

  if (!$rows) {
    echo "No missing-city rows found.\n";
    exit(0);
  }

  $postIds = array_map(fn($r)=>(int)$r['wp_post_id'], $rows);
  $metaByPostId = [];
  foreach (array_chunk($postIds, 400) as $chunk) {
    $in = implode(',', array_fill(0, count($chunk), '?'));
    $s = $wp->prepare("SELECT post_id, meta_key, meta_value FROM {$pfx}postmeta WHERE post_id IN ({$in}) AND meta_key IN ('city','_city','business_city','lp_listingpro_options')");
    $s->execute($chunk);
    foreach ($s->fetchAll() as $m) {
      $metaByPostId[(int)$m['post_id']][(string)$m['meta_key']] = (string)$m['meta_value'];
    }
  }

  $upd = $mci->prepare("UPDATE mci_business_branches SET city = ?, updated_at = NOW(6) WHERE id = ?");
  $updated = 0; $checked = count($rows);
  foreach ($rows as $r) {
    $postId = (int)$r['wp_post_id'];
    $branchId = (string)$r['branch_id'];
    $meta = $metaByPostId[$postId] ?? [];
    $city = trim((string)($meta['city'] ?? $meta['_city'] ?? $meta['business_city'] ?? ''));
    if ($city === '') {
      $lp = @unserialize((string)($meta['lp_listingpro_options'] ?? ''));
      if (is_array($lp)) {
        $city = mci_city_from_gaddress_heuristic((string)($lp['gAddress'] ?? '')) ?? '';
      }
    }
    if ($city !== '') {
      $upd->execute([$city, $branchId]);
      $updated++;
    }
  }

  echo "Missing-city heuristic remediation complete.\n";
  echo "- Checked missing-city branches: {$checked}\n";
  echo "- Updated city values: {$updated}\n";
} catch (Throwable $e) {
  fwrite(STDERR, "Heuristic repair failed: ".$e->getMessage().PHP_EOL);
  exit(1);
}

