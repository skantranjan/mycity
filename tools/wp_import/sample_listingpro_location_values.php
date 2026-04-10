<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_req(string $k): string { $v = getenv($k); if (!is_string($v) || trim($v)==='') throw new RuntimeException("Missing {$k}"); return trim($v); }
function env_opt(string $k, ?string $d=null): ?string { $v=getenv($k); return (is_string($v)&&trim($v)!=='')?trim($v):$d; }
function pdo_mysql(string $h,string $db,string $u,string $p,?string $port=null): PDO {
  $pp = ($port!==null&&$port!=='')?';port='.(int)$port:'';
  return new PDO("mysql:host={$h}{$pp};dbname={$db};charset=utf8mb4",$u,$p,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function detect_wp_prefix(PDO $wp): string {
  $cfg=env_opt('WP_TABLE_PREFIX'); if($cfg!==null) return $cfg;
  foreach($wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r){$t=(string)($r[0]??''); if(str_ends_with($t,'posts')) return substr($t,0,-5);}
  throw new RuntimeException('No WP prefix');
}

try {
  $mci = pdo_mysql(env_req('MCI_DB_HOST'),env_req('MCI_DB_NAME'),env_req('MCI_DB_USER'),env_req('MCI_DB_PASS'),env_opt('MCI_DB_PORT'));
  $wp = pdo_mysql(env_req('WP_DB_HOST'),env_req('WP_DB_NAME'),env_req('WP_DB_USER'),env_req('WP_DB_PASS'),env_opt('WP_DB_PORT'));
  $pfx = detect_wp_prefix($wp);
  $ids = $mci->query("SELECT source_id FROM mci_wp_import_map WHERE source_type='wp_post' AND target_type='mci_business_group' LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
  $in = implode(',', array_fill(0, count($ids), '?'));
  $s = $wp->prepare("SELECT post_id, meta_value FROM {$pfx}postmeta WHERE meta_key='lp_listingpro_options' AND post_id IN ({$in})");
  $s->execute(array_map('intval',$ids));
  foreach($s->fetchAll() as $r){
    $o=@unserialize((string)$r['meta_value']); if(!is_array($o)) continue;
    echo "post_id=".(int)$r['post_id']."\n";
    foreach(['gAddress','mappin','latitude','longitude','phone','website','whatsapp','email'] as $k){
      $v = isset($o[$k]) ? trim((string)$o[$k]) : '';
      if($v!=='') echo "  {$k}: {$v}\n";
    }
  }
} catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1);}

