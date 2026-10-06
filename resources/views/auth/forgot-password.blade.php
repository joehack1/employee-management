<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LeaveFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <h2 class="text-2xl font-bold text-slate-900">Reset your password</h2>
        <p class="mt-1 text-xs text-slate-500">Enter your registered email address and we'll send you instructions.</p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-slate-100 sm:px-10">
            @if(session('status'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs border border-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form class="space-y-4" action="{{ route('password.email') }}" method="POST">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">Email address</label>
                    <input id="email" name="email" type="email" required placeholder="name@company.com" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl sm:text-sm">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition">
                    Send Reset Link
                </button>
                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-800">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
