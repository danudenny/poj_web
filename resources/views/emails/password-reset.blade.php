<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="background: #F2F2F2; padding: 20px 10%">
<div style="background: #FFFFFF; padding: 20px 0%; margin-top: 20px;border-radius: 5px">
    <p style="text-align: center">
        <img src="https://alakad.optimajasa.co.id:9000/att-poj-bucket/uploads/6506d33bba8f6_64d8f8539ce0e_logo_main-b7587f52.png">
    </p>
</div>

<div style="background: #FFFFFF; padding: 20px; font-family: tahoma, sans-serif; margin-top: 20px; align-items: center; justify-content: flex-start; border-radius: 5px">
    <div style="color: #353638; font-size: 15px">
        <p><b>Halo {{ $data['fullname'] }},</b></p>
        <p>Berikut ini merupakan akun terbaru anda untuk mengakses aplikasi ALAKAD.</p>
    </div>
    <div style="padding-top: 10px">
        <p style="text-align: center; color: #353638;">Username:</p>
        <div style="background: #126850; border-radius: 10px; padding: 5px; color: #FFF; font-size: 20px">
            <p style="text-align: center;color:white"><b><a href="#" style="color:white;text-decoration: none;">{{ $data['email'] }}</a> </b></p>
        </div>
    </div>
    <div style="padding-top: 10px">
        <p style="text-align: center; color: #353638;">Password baru:</p>
        <div style="background: #126850; border-radius: 10px; padding: 5px; color: #FFF; font-size: 20px">
            <p style="text-align: center"><b>{{ $data['new_password'] }}</b></p>
        </div>
    </div>
    <div style="margin-top: 30px; color: #353638; font-size: 15px">
        <p>Mobile : <a href="https://google.com">https://google.com</a></p>
        <p>Web : <a href="http://alakad.optimajasa.co.id/">http://alakad.optimajasa.co.id/</a></p>
    </div>
    <div style="color: #353638; font-size: 15px; margin-top: 30px">
        <p>Terimakasih,</p>
        <p>Tim Aplikasi ALAKAD</p>
    </div>
</div>
</body>
</html>
