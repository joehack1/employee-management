<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'LeaveFlow') }}</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f3f4f6; color: #1f2937; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px;">
        <tr>
            <td style="padding: 28px 32px; background-color: #0f766e; color: #ffffff; border-radius: 8px 8px 0 0; font-size: 20px; font-weight: bold;">
                {{ config('app.name', 'LeaveFlow') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 32px; font-size: 15px; line-height: 1.6;">
                @isset($greeting)
                    <p style="margin: 0 0 18px;">{{ $greeting }}</p>
                @endisset

                @foreach ($lines ?? [] as $line)
                    <p style="margin: 0 0 14px;">{{ $line }}</p>
                @endforeach

                @isset($action)
                    <p style="margin: 26px 0;">
                        <a href="{{ $action['url'] }}" style="display: inline-block; padding: 12px 20px; border-radius: 6px; background-color: #0f766e; color: #ffffff; font-weight: bold; text-decoration: none;">{{ $action['label'] }}</a>
                    </p>
                @endisset

                @isset($salutation)
                    <p style="margin: 22px 0 0;">{{ $salutation }}</p>
                @endisset
            </td>
        </tr>
    </table>
</body>
</html>
