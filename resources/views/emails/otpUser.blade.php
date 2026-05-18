<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد حسابك</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 600px; background-color: #ffffff; margin: 20px auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <tr>
            <td align="center" style="padding-bottom: 20px; border-bottom: 1px solid #eeeeee;">
                <h2 style="color: #2c3e50; margin: 0; font-size: 24px;">مكتبتي الرقمية</h2>
            </td>
        </tr>

        <tr>
            <td style="padding: 30px 0 20px 0; text-align: right;">
                <p style="font-size: 18px; color: #333333; margin: 0; font-weight: bold;">مرحباً بك معنا!</p>
                <p style="font-size: 15px; color: #555555; line-height: 1.6; margin-top: 10px;">
                    شكراً لتسجيلك في منصتنا. لإكمال عملية تفعيل حسابك والتحقق من بريدك الإلكتروني، يرجى استخدام رمز
                    التأكيد (OTP) المؤقت التالي:
                </p>
            </td>
        </tr>

        <tr>
            <td align="center" style="padding: 20px 0;">
                <div
                    style="background-color: #f8fafc; border: 2px dashed #3b82f6; color: #1e3a8a; font-size: 32px; font-weight: bold; letter-spacing: 6px; padding: 15px 30px; border-radius: 6px; display: inline-block;">
                    {{ $otp }}
                </div>
            </td>
        </tr>

        <tr>
            <td style="text-align: right; padding-bottom: 30px;">
                <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                    * ملاحظة: هذا الرمز صالح لمدة 10 دقائق فقط من تاريخ إرساله. إذا لم تكن أنت من قام بهذا الطلب، يمكنك
                    تجاهل هذا الإيميل بأمان.
                </p>
            </td>
        </tr>

        <tr>
            <td align="center"
                style="padding-top: 20px; border-top: 1px solid #eeeeee; font-size: 12px; color: #94a3b8;">
                <p style="margin: 0;">جميع الحقوق محفوظة © {{ date('Y') }} مكتبتي الرقمية</p>
            </td>
        </tr>
    </table>

</body>

</html>