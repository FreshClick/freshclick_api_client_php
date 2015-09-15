<?php 
define('COUNTER_ID', 56935568);

require_once __DIR__.'/vendor/autoload.php';
$token=!empty($_GET['token'])?$_GET['token']:null;
function echoTest($code,$result,$freshclick) {
    if($result){
        $render='<pre>'.json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</pre>';  
    }
    else{
        $render="<span style='color:red'>{$freshclick->error_info}</span>";
    }
    $code=  htmlspecialchars($code);
    echo "<p><pre>$code</pre><br>Result:<br>$render</p>";
}
?>

<!DOCTYPE html> 
<html lang="ru" prefix="og: http://ogp.me/ns#">
<head>
    <title>FreshClick - сервис для анализа и оптимизации рекламы в интернете</title>
	
<!--FreshClick counter-->
<script type="text/javascript">
        var freshclick = freshclick || [];
        freshclick.id = <?=COUNTER_ID?>; //ID счётчика
        !function(e){setTimeout(function(){var t=document,c=t.getElementsByTagName
        ("script")[0],a=t.createElement("script");a.type="text/javascript",
        a.async=!0,a.src=e,c.parentNode.insertBefore(a,c)},1)}
        ("//api.fc/js?_p="+freshclick.id+"&t="+86400*(+new Date/864e5|0));
		
</script>
<!--/FreshClick counter-->
	    
</head>
<body>
    <form method="get" >
        Введите токен:
        <input type="text" name="token" value="<?=htmlentities($token)?>">
        <button type="submit">Test!</button>
    </form>

<?php
if($token){
    $freshclick=new \FreshClick\FreshClick($token); 
    $result=$freshclick->send('hello',['message'=>'Hello World!']);
    echoTest('$freshclick->send("hello",["message"=>"Hello World!"])', $result, $freshclick);

    $result=$freshclick->client('Иванов','ivanov@mail.ru','+79161234567')->send('register');
    $freshclick->addGood('77-78878', 'iPhone', 35000.40,5)
               ->addGood(444, 'MacBook', 90556)
               ->order(4554, 88455)
               ->send();
    echoTest("\$freshclick->addGood('77-78878', 'iPhone', 35000.40,5)
               ->addGood(444, 'MacBook', 90556)
               ->order(4554, 88455)
               ->send();", $result, $freshclick);
    
}

?>


</body>
</html>    