<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>FireWatch 24/7 - Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }
        .emergency-bg {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            position: relative;
            overflow: hidden;
        }
        .emergency-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
        }
        .fire-icon {
            animation: flicker 3s infinite;
        }
        @keyframes flicker {
            0%, 100% { opacity: 1; }
            33% { opacity: 0.8; transform: scale(1.02); }
            66% { opacity: 0.9; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center fire-icon">
                        <i class="fa-solid fa-fire-extinguisher text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Fire<span class="text-red-600">Watch</span></span>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-red-600 font-medium">Home</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Emergency</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Services</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Resources</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Contact</a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-4">
                    <a href="#" class="text-red-600 font-medium">Login</a>
                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                        <i class="fa-solid fa-phone-alt"></i>
                        <span class="hidden sm:inline">Emergency</span>
                    </button>
                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden text-gray-700">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content - Landscape Layout -->
    <section class="flex-1 flex items-center justify-center p-4 lg:p-8">
        <div class="w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="flex flex-col lg:flex-row">
                <!-- Left Side - Emergency Branding & Design -->
                <div class="lg:w-1/2 emergency-bg text-white p-8 lg:p-12 flex flex-col justify-between relative">
                    <div class="relative z-10">
                        <!-- Emergency Header -->
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-fire-extinguisher text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">FireWatch 24/7</h3>
                                <p class="text-white/80 text-sm">Emergency Response System</p>
                            </div>
                        </div>

                        <!-- Main Emergency Message -->
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold">ACTIVE 24/7</span>
                                <span class="px-3 py-1 bg-yellow-500/30 rounded-full text-sm font-semibold">LIVE ALERTS</span>
                            </div>
                            <h1 class="text-4xl lg:text-5xl font-bold mb-4">
                                Rapid Response<br>to Any Emergency
                            </h1>
                            <p class="text-white/90 text-lg leading-relaxed">
                                Access your dashboard to monitor real-time emergencies, coordinate response teams, and manage fire safety protocols across your jurisdiction.
                            </p>
                        </div>

                        <!-- Emergency Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                                <div class="text-3xl font-bold mb-1">24/7</div>
                                <div class="text-white/80 text-sm">Coverage</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                                <div class="text-3xl font-bold mb-1">3min</div>
                                <div class="text-white/80 text-sm">Avg Response</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                                <div class="text-3xl font-bold mb-1">50+</div>
                                <div class="text-white/80 text-sm">Fire Stations</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                                <div class="text-3xl font-bold mb-1">500+</div>
                                <div class="text-white/80 text-sm">Personnel</div>
                            </div>
                        </div>

                        <!-- Emergency Contact Badge -->
                        <div class="flex items-center gap-3 bg-black/20 rounded-xl p-4">
                            <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center animate-pulse">
                                <i class="fa-solid fa-phone-alt text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm text-white/80">Emergency Hotline</p>
                                <p class="text-2xl font-bold">911</p>
                            </div>
                        </div>
                    </div>

                    <!-- Decorative Elements -->
                    <div class="absolute bottom-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute top-20 right-20 w-32 h-32 bg-yellow-500/10 rounded-full blur-2xl"></div>
                </div>

                <!-- Right Side - Login Form -->
                <div class="lg:w-1/2 p-8 lg:p-12 bg-white">
                    <div class="max-w-md mx-auto">
                        <!-- Form Header -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900">Welcome Back</h2>
                            <p class="mt-2 text-gray-600">
                                Sign in to access your emergency dashboard
                            </p>
                        </div>

                        <!-- Emergency Alert Banner (Compact) -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-6 rounded-r-lg">
                            <div class="flex items-center">
                                <i class="fa-solid fa-triangle-exclamation text-yellow-400 mr-2"></i>
                                <p class="text-xs text-yellow-700">
                                    For emergencies, call <span class="font-bold">911</span> immediately
                                </p>
                            </div>
                        </div>

                        <!-- Login Form -->
                        <form class="space-y-6" method="POST" action="{{ route('auth.authenticate') }}">
                        @csrf   
                            <!-- CSRF Token (for Laravel) -->
                         

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" id="email" name="email" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                        placeholder="firefighter@firewatch.com"
                                        value="{{ old('email') }}">
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                        placeholder="••••••••">
                                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center toggle-password">
                                        <i class="fa-solid fa-eye text-gray-400 hover:text-gray-600"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" id="remember" name="remember" 
                                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                                        Remember me
                                    </label>
                                </div>
                                <a href="#" class="text-sm font-medium text-red-600 hover:text-red-500">
                                    Forgot password?
                                </a>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" 
                                class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                Sign In to Dashboard
                            </button>

                            <!-- Quick Access (Optional - can be removed in production) -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Quick Access</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" 
                                    onclick="fillCredentials('firefighter@firewatch.com', 'password123')"
                                    class="w-full inline-flex justify-center items-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-user-firefighter text-red-600 mr-2"></i>
                                    Firefighter
                                </button>
                                <button type="button" 
                                    onclick="fillCredentials('admin@firewatch.com', 'admin123')"
                                    class="w-full inline-flex justify-center items-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-shield-halved text-red-600 mr-2"></i>
                                    Admin
                                </button>
                            </div>

                            <!-- Sign Up Link -->
                            <div class="text-center pt-4">
                                <p class="text-sm text-gray-600">
                                    Don't have an account? 
                                    <a href="{{ route('register') }}" class="font-medium text-red-600 hover:text-red-500">
                                        Register as responder
                                    </a>
                                </p>
                            </div>
                        @if ($errors->any())
                            <div class="text-red-500 mt-2">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        </form>

                        <!-- Security Notice -->
                        <div class="text-center text-xs text-gray-500 mt-6">
                            <i class="fa-solid fa-shield-halved mr-1"></i>
                            Secured by FireWatch 24/7 Emergency System
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (Simplified) -->
    <footer class="bg-gray-900 text-white py-4">
        <div class="container mx-auto px-4 text-center text-gray-400 text-xs">
            <p>©2024 FireWatch 24/7 Emergency Response System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Password Toggle Script -->
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.relative').querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Fill credentials for demo
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Highlight the fields briefly
            document.getElementById('email').classList.add('ring-2', 'ring-green-500');
            document.getElementById('password').classList.add('ring-2', 'ring-green-500');
            
            setTimeout(() => {
                document.getElementById('email').classList.remove('ring-2', 'ring-green-500');
                document.getElementById('password').classList.remove('ring-2', 'ring-green-500');
            }, 1000);
        }

        // // Form submission handler (for demo)
        // document.querySelector('form').addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     const email = document.getElementById('email').value;
        //     const password = document.getElementById('password').value;
            
        //     if (email && password) {
        //         // Show loading state
        //         const button = this.querySelector('button[type="submit"]');
        //         const originalText = button.innerHTML;
        //         button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in...';
        //         button.disabled = true;
                
        //         // Simulate login
        //         setTimeout(() => {
        //             // alert('Demo: Redirecting to emergency dashboard...');
        //             button.innerHTML = originalText;
        //             button.disabled = false;
        //         }, 1500);
        //     }
        // });
    </script>
    <script>
    // Prevent back button to login page after successful login
    (function() {
        // Push a new state to the history
        history.pushState(null, null, location.href);
        
        // Listen for popstate events (back/forward buttons)
        window.addEventListener('popstate', function() {
            // Check if user is already authenticated
            fetch('/check-auth', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    // If authenticated, redirect to dashboard
                    window.location.replace('{{ route("citizen.dashboard") }}');
                } else {
                    // If not authenticated, push state again
                    history.pushState(null, null, location.href);
                }
            })
            .catch(() => {
                history.pushState(null, null, location.href);
            });
        });

        // Handle page show event (when page is loaded from bfcache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was loaded from back/forward cache
                fetch('/check-auth')
                    .then(response => response.json())
                    .then(data => {
                        if (data.authenticated) {
                            window.location.replace('{{ route("citizen.dashboard") }}');
                        }
                    });
            }
        });
    })();
</script>
</body>
</html>