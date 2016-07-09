<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Welcome</title>
</head>
<body>

<div style="width:100%;" align="center">
    <table width="740" border="0" cellspacing="0" cellpadding="0">
        <thead width:
        "100%" style="background-color:#D19037; color:#FFFFFF; font-size:36px; font-family: Helvetica, Arial,
        sans-serif;">
        <tr style="height:202px;">
            <th style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; padding-right: 260px;"
                align="right"><?= $this->Html->image("logo_reaxium.png", array('fullBase' => true,'style'=>'width:230px;
                height:150px')) ?>
            </th>
            <!--<th style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; padding-right: 260px;" align="right"><img src="img/logo_reaxium.png" style="width:230px; height:150px;"><br></th> -->
        </tr>
        </thead>
    </table>
    <table width="740" border="0" cellspacing="0" cellpadding="0" align="center">
        <tbody style="background-color: #eeedf2;">
        <tr>
            <td style="color:#5d5d5d; font-size:26px; font-family: Helvetica, Arial, sans-serif; line-height: 131%; padding-left: 17%; padding-top: 88px;">
                Welcome to Reaxium, <?= $parentName ?></td>
        </tr>
        <tr>
            <td style="padding-left: 17%; color:#5d5d5d; font-size:26px; font-family: Helvetica, Arial, sans-serif; padding-top: 50px;">
                A reliable, efficient and fun way to track your children. <br> You can:
            </td>
        </tr>
        <tr>
            <td style="padding-top: 15px;">
                <ul style="padding-left: 21%; color: #4a4a4a; font-size: 26px; font-family: Helvetica, Arial, sans-serif; font-weigh: normal; line-height: 131%; padding-right:10%;">
                    <li style="padding-top: 5px;">Be notified everytime a student gets on or off the bus</li>
                    <li style="padding-top: 5px;">Be notified when the bus is close to the stop of one of the
                        students.
                    </li>
                    <li style="padding-top: 5px;">Be notified of any delay in the route</li>
                    <li style="padding-top: 5px;">Be notified of any problem in the system</li>
                    <li style="padding-top: 5px;">Track your student while on the bus</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td style="padding-left: 17%; color: #4a4a4a; font-size: 26px; font-family: Helvetica, Arial, sans-serif; font-weigh: normal; line-height: 131%; padding-top:25px;">
                Use our mobile app with your Reaxium Account:
            </td>
        </tr>
        <tr>
            <td style="padding-left: 17%; color: #4a4a4a; font-size: 26px; font-family: Helvetica, Arial, sans-serif; font-weigh: normal; line-height: 131%; padding-top:25px;">
                Username:  <?= $parentUserName ?></td>
        </tr>
        <tr>
            <td style="padding-left: 17%; color: #4a4a4a; font-size: 26px; font-family: Helvetica, Arial, sans-serif; font-weigh: normal; line-height: 131%; padding-top:25px;">
                Password:  <?= $parentPassword ?></td>
        </tr>
        <tr>
            <td style="color: #ec5d2f; font-size: 26px; padding-left: 17%; font-family: Helvetica, Arial, sans-serif; font-weigh: normal; padding-top: 46px; padding-bottom: 30px;">
                Thanks for joinning us.
            </td>
        </tr>

        <tr>
            <td style="color: #ec5d2f; font-size: 10px; padding-left: 17%; font-family: Helvetica, Arial, sans-serif; font-weigh: normal;">

                <a href="https://itunes.apple.com/new_york/app/"><?= $this->Html->image("appstore.png", array('fullBase'
                    => true,'style'=>'width: 220px; height: 70px;')) ?></a>
                <a href="https://play.google.com/store/apps/details?id=com.eduardoluttinger.reaxiumforparents&hl=en"><?= $this->
                    Html->image("playstore.png", array('fullBase' => true,'style'=>'width: 220px; height: 70px;
                    margin-left:75px')) ?></a>

            </td>
        </tr>

        </tbody>
    </table>

</div>
</body>
</html>