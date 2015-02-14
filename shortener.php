
<?php
include 'simple_html_dom.php';
function do_post_request_googl($url, $data , $header = ''){
  $options = array(
	  'http' => array(
	    'method'  => 'POST',
	    'content' => json_encode( $data ),
	    'header'=>  "Content-Type: application/json\r\n" .
	                "Accept: application/json\r\n"
	    )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents( $url, false, $context );
  	return $result;
}

function send_post($url , $data , $header = '')
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	$contents = curl_exec($ch);

	curl_close($ch);
	return $contents;
}

function parse_tinyURL($data){
	$html = str_get_html($data);
	$div = $html->find('div[id=copyinfo]');
	if(sizeof($div)==0)
		return "";
	$div = (array)$div[0];
	$div = $div['attr'];
	return $div['data-clipboard-text'];
}


function parse_1url($data){
	$html = str_get_html($data);
	$div = $html->find('input[name=newurl]');
	return $div[0]->value;
}

function parse_2gp($data){
	$html = str_get_html($data);
	$div = $html->find('a');
	$href =  $div[0]->href;
	return str_replace("share/", "", $href);
}

function parse_snim($data ,$find = 'input[name=SNIPPED]' ){
	try {
		$html = str_get_html($data);
		$div = $html->find($find);
		return $div[0]->value;
	} catch (Exception $e) {
		return "";
	}
}

function parse_coinurl($data , $find = 'b' , $n = 0 ){
	try {
		$html = str_get_html($data);
		$div = $html->find($find);
		return $div[$n]->plaintext;
	} catch (Exception $e) {
		return "";
	}
}

if(!isset($_POST['url']))
{
	$return = [];
	die(json_encode($return));
}

$longUrl = $_POST['url'];
if(substr($longUrl,0,4)!="http"){
	$longUrl = "http://".$longUrl;
}

$s = 0;
if(isset($_POST['start'])){
	try {
		$s = intval($_POST['start']);
	} catch (Exception $e) {
		$s = 0;
	}
}

$shorten_url = array();

if($s==0){
	// goo.gl 
	$url = 'https://www.googleapis.com/urlshortener/v1/url';
	$data = array( 'longUrl'=>$longUrl);
	$data = json_decode(do_post_request_googl($url , $data));

	if(isset($data->id))
		array_push($shorten_url, $data->id);
	
	// bit.ly 
	$data = json_decode(file_get_contents('https://api-ssl.bitly.com/v3/shorten?access_token=daa458b231e04be3d10ac79edfba601bf911a9c6&longUrl='.urlencode($longUrl)));
	if($data->status_code==200)
		array_push($shorten_url, $data->data->url);
	$data = json_decode(file_get_contents('https://api-ssl.bitly.com/v3/shorten?access_token=daa458b231e04be3d10ac79edfba601bf911a9c6&domain=j.mp&longUrl='.urlencode($longUrl)));
	if($data->status_code==200)
		array_push($shorten_url, $data->data->url);
	$data = json_decode(file_get_contents('https://api-ssl.bitly.com/v3/shorten?access_token=daa458b231e04be3d10ac79edfba601bf911a9c6&domain=bitly.com&longUrl='.urlencode($longUrl)));
	if($data->status_code==200)
		array_push($shorten_url, $data->data->url);

	// TinyURL
	$data =  file_get_contents("http://tinyurl.com/create.php?url=".$longUrl);
	$data = parse_tinyURL($data);
	if($data != "")
		array_push($shorten_url, $data);
}

else if($s==1){
	$data = file_get_contents('http://api.adf.ly/api.php?key=c02fe2b360ee4b566a4f1e14d84b279b&uid=3141484&advert_type=banner&domain=adf.ly&url='.$longUrl);	
	if($data[0] != "{")
		array_push($shorten_url, $data);

	$data = file_get_contents('http://api.adf.ly/api.php?key=c02fe2b360ee4b566a4f1e14d84b279b&uid=3141484&advert_type=banner&domain=q.gs&url='.$longUrl);	
	if($data[0] != "{")
		array_push($shorten_url, $data);

	$data = file_get_contents("http://0rz.tw/create?url=".urlencode($longUrl));
	$data = parse_snim($data,'input[id=link]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('u' => $longUrl);
	$url = 'http://1url.com/';
	$data = parse_snim(send_post($url,$data),'input[name=newurl]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'expirein'=>'31536000' , 'submit' => 'shorten');
	$url = 'http://2.gp/';
	$data = parse_2gp(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);
}

else if($s==2){

	$data = array('url' => $longUrl , 'expirein'=>'31536000' , 'submit' => 'shorten');
	$url = 'http://qr.net/';
	$data = parse_2gp(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'expirein'=>'31536000' , 'submit' => 'shorten');
	$url = 'http://7.ly/';
	$data = parse_2gp(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'submit1'=>'Snip it!');
	$url = 'http://sn.im/';
	$data = parse_snim(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'submit1'=>'Snip it!');
	$url = 'http://snipurl.com/';
	$data = parse_snim(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'submit1'=>'Snip it!');
	$url = 'http://snipr.com/';
	$data = parse_snim(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==3){
	$data = array('url' => $longUrl , 'submit1'=>'Snip it!');
	$url = 'http://snurl.com/';
	$data = parse_snim(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('pralina' => $longUrl);
	$url = 'http://tiny.pl/';
	$data = parse_snim(send_post($url,$data) , 'input[id=skrot]');
	if($data != "")
		array_push($shorten_url, $data); 

	$data = array('url' => $longUrl);
	$url = 'http://is.gd/create.php';
	$data = parse_snim(send_post($url,$data) , 'input[id=short_url]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://v.gd/create.php';
	$data = parse_snim(send_post($url,$data) , 'input[id=short_url]');
	if($data != "")
		array_push($shorten_url, $data); 
	
	$data = array('url' => $longUrl , 'subdomain' => 'bit');
	$url = 'https://coinurl.com/shorten.php';
	$data = parse_coinurl(send_post($url,$data));
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==4){
	$data = file_get_contents("http://moourl.com/create/?source=".urlencode($longUrl)."&x=30&y=21");
	$data = parse_coinurl($data , 'div[id=milked_url]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('starturl' => $longUrl , 'submitted' => 'TRUE' );
	$url = 'http://nutshellurl.com/createnut';
	$data = parse_snim(send_post($url,$data) , 'input[id=nutresult]');
	if($data != "")
		array_push($shorten_url, $data);


	$data = file_get_contents("http://shorl.com/create.php?url=".urlencode($longUrl)."&go=Shorlify%21");
	$data = parse_coinurl($data , 'a[rel=nofollow]');
	if($data != "")
		array_push($shorten_url, $data);
	

	$data = array('a8946344304' => $longUrl);
	$url = 'http://www.shorturl.com/make_url.php';
	$data = parse_snim(send_post($url,$data) , 'input[id=a9088923242]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('longurl' => $longUrl , 'submit' => 'shorten »' );
	$url = 'http://yep.it/';
	$data = parse_coinurl(send_post($url,$data) , 'textarea[id=box-content]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==5){
	$data = array('url' => $longUrl );
	$url = 'http://aka.al/';
	$data = parse_snim(send_post($url,$data) , 'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
	

	$data = file_get_contents("http://durl.me/Create.do?longurl=".urlencode($longUrl));
	$data = parse_coinurl($data , 'textarea[id=shortURLArea]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'do' => 'shorten' , 'sbmt' => 'Shorten' );
	$url = 'http://cli.gs/';
	$data = parse_snim(send_post($url,$data) , 'input[name=shortened_url]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://wtc.la/';
	$data = parse_snim(send_post($url,$data) , 'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://www.sv.pl/ajax.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);
}
else if($s==6){
	$data = file_get_contents("http://smsh.me/?save=y&url=".urlencode($longUrl)."&doit=Mash+URL");
	$data = parse_coinurl($data , 'div[class=mainbody]');
	$data = parse_coinurl($data , 'a');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://corta.co/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://dl4.pl/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
	
	$data = file_get_contents("http://ux.nu/api/short?url=".urlencode($longUrl));
	$data = json_decode($data);
	if(isset($data->data->url))
		array_push($shorten_url, $data->data->url);
	
	$data = array('url' => $longUrl );
	$url = 'http://urlsnoop.com/create.php';
	$data = parse_snim(send_post($url,$data),'input[id=appendedInputButton]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==7){
	$data = array('url' => $longUrl );
	$url = 'http://prosperity-link.com/create.php';
	$data = parse_snim(send_post($url,$data),'input[id=appendedInputButton]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://off.st';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://linkprosperity.com/create.php';
	$data = parse_snim(send_post($url,$data),'input[id=appendedInputButton]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://i3i.biz/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://cut-it.net';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==8){
	$data = array('url' => $longUrl );
	$url = 'http://m-tg.co/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('fullurl' => $longUrl  , 'rnd' => '181430370');
	$url = 'http://rarme.com/';
	$data = parse_coinurl(send_post($url,$data),'span[id=copytext]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://onj.me/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl);
	$url = 'http://ve.ma/create.php';
	$data = parse_snim(send_post($url,$data) , 'input[id=appendedInputButton]');
	if($data != "")
		array_push($shorten_url, $data);
	
	$data = array('url' => $longUrl );
	$url = 'http://ci8.de/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==9){
	$data = array('url' => $longUrl );
	$url = 'http://hx.pl/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
	
	$data = array('url' => $longUrl );
	$url = 'http://fc.cx/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://e50.us/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://tvhl.co/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://ur7.us/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==10){
	$data = array('url' => $longUrl );
	$url = 'http://wck.bz/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://dodatkikrawieckie.info.pl/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'form' => 'shrinkNew');
	$url = 'http://l0.kz/';
	$data = parse_snim(send_post($url,$data),'input[readonly=]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://2u4.us/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://axr.be';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
}
else if($s==11){
	$data = array('url' => $longUrl );
	$url = 'http://ck2.it/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);
	
	$urls = ['http://1h.ae' ,'http://1to.to' ,'http://bbo.jp' ,'http://by.ix-cafe.com'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else if($s==12){
	$data = array('url' => $longUrl );
	$url = 'http://flsd.co/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl );
	$url = 'http://e5a.co/';
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://ecza.tk/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);


	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://urlboy.com/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);

	$data = array('url' => $longUrl );
	$url = 'http://past.is/api/';
	$data = json_decode(send_post($url,$data));
	if(isset($data->shorturl))
		array_push($shorten_url, $data->shorturl);

}
else if($s==13){
	$urls = ['http://ur9.us/' , 'http://ur0.us/' , 'http://ur6.us/' ,'http://us9.co' , 'http://wxc.co/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else if($s==14){
	$urls = ['http://s2p.at/', 'http://3big.com/' , 'http://e40.us/' , 'http://dkd.li/' , 'http://e60.us/' , 'http://urlz.gr/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else if($s==15){
	$url = 'http://o2s.org/index.php?a=short';
	$data = array('url' => $longUrl );
	$data = parse_snim(send_post($url,$data),'input[id=url]');
	if($data != "")
		array_push($shorten_url, $data);
	

	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://d2.ae/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);


	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://blbuh.ru/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);

	$url = 'http://mv2.me/index.php';
	$data = array('url' => $longUrl , 'event_date' => '2/7/19 4:00' );
	$data = parse_snim(send_post($url,$data),'input[id=copylink]');
	if($data != "")
		array_push($shorten_url, $data);


	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://aaa.yt/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);
}
else if($s==16){
	$urls = ['http://tiny6.com/' , 'http://yus.at/' , 'http://url.bz/' , 'http://xed.cc/' , 'http://zag.su/' , 'http://mgr.pl/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else if($s==17){
	$urls = ['http://mzy.in/' , 'http://pdx.me/' , 'http://xn.al' , 'http://bl.gd/' , 'http://nq.st/' , 'http://nsu.pe/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
			array_push($shorten_url, $data);
		
	}
}
else if($s==18){
	$urls = ['http://gag.pw/', 'http://ph0.to/' ,'https://spna.ca/' , 'http://bp7.org' , 'http://adul.tc/' , 'http://cvk.biz/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
			array_push($shorten_url, $data);
	}
}
else if($s==19){
	$urls = ['http://www.tfz.me/' ,'http://mrch.me/' , 'http://urlr.be/short/?id=' , 'http://mze.me/' , 'http://boobs4.us/' ,'http://yourls.sci.io/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else if($s==20){
	$url = "http://bb-u.de/	";
	$data = array('url' => $longUrl );
	$data = parse_snim(send_post($url,$data),'input[id=jo]');
	if($data != "")
		array_push($shorten_url, $data);

	$url = "http://katt.it/?module=ShortURL&file=Add&mode=short	";
	$data = array('url' => $longUrl );
	$data = parse_snim(send_post($url,$data),'input');
	if($data != "")
		array_push($shorten_url, $data);
	$url = "https://chrst.ph/";
	$data = array('url' => $longUrl );
	$data = parse_snim(send_post($url,$data),'input[id=callback-paragraph]');
	if($data != "")
		array_push($shorten_url, $data);

	$data = array('url' => $longUrl , 'action' => 'shorten');
	$url = 'http://smallurl.co/application.php';
	$data = json_decode(send_post($url,$data));
	if(isset($data->short))
		array_push($shorten_url, $data->short);
}
else if($s==21){
	$urls = ['https://ira.li/' , 'http://schad.es/' , 'http://www.larsh.nl/' , 'http://tlink.pl/' , 'http://brofi.st/' , 'http://dhurl.net/'];
	foreach ($urls as $key => $url) {
		$data = array('url' => $longUrl );
		$data = parse_snim(send_post($url,$data),'input[id=copylink]');
		if($data != "")
		{
			array_push($shorten_url, $data);
		}
	}
}
else{

}

echo json_encode($shorten_url);