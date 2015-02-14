<html>
<head>
	<title>URL Shortener</title>
</head>
<body>
	<center>
		<h1>
			URL Shortener API
		</h1>
		<br>
		<input id="url" name="url" placeholder="Enter your url here"><br>
		<button id="submit">GO</button><br><br>
		<ol id="result">
			
		</ol>
		<hr>
	</center>
	<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
	<script type="text/javascript">
		$("#submit").click(function(e){
			var data={};
			$('#result').html("");
			var count = 0;
			data['url']=$('#url').val();
			for (var i =  21; i >= 0; i--) {
				data['start']=i;
				jQuery.ajax({
					url : "/shortener.php",
					data:data,
					type : "post",
					success:function(data){
						data=JSON.parse(data);
						for (var j = data.length - 1; j >= 0; j--) {
							var str=data[j];
							str= '<li><a href="'+str+'">'+str+'</a><br></li>';
							$('#result').append(str);
							count++;
						};
					}
				})
			};
		})
	</script>
</body>
</html>