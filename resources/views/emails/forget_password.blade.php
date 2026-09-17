
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <main class="responsive">
		<h4>Send from Dashboard</h4>
		<p>請點擊以下連結，開啟密碼重設頁面</p>
		<a href="{{ $link }}">忘記密碼重設</a>
		<p style="color:red">此連結將於{{$expiredMins}}分鐘後失效</p></br></br>
		<p style="color:red">*** 本信件由系統自動發送，請勿直接回覆 ***</p>
	</main>
</body>
</html>

