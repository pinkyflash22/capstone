PyroSense

@if ($purpose === 'password-reset')
Reset your password
Enter this code in PyroSense to continue resetting your password.
@elseif ($purpose === 'sign-in')
Confirm your sign-in
Enter this code in PyroSense to finish signing in with Google.
@else
Verify your email
Welcome to PyroSense. Enter this code to verify your email and continue creating your account.
@endif

Your verification code: {{ $otpCode }}

This code expires in 10 minutes.
Keep this code private. Never share it with anyone.

Didn't request this code? You can safely ignore this email.

This is an automated message from PyroSense.
