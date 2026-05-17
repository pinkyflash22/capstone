<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>PyroSense - Register</title>
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
                    <span class="text-xl font-bold text-gray-800">Pyro<span class="text-red-600">Sense</span></span>
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
                    <a href="#" class="text-red-600 font-medium">Register</a>
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
                <!-- Left Side - Emergency Branding & Design (SAME AS LOGIN) -->
                <div class="lg:w-1/2 emergency-bg text-white p-8 lg:p-12 flex flex-col justify-between relative">
                    <div class="relative z-10">
                        <!-- Emergency Header -->
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-fire-extinguisher text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">PyroSense</h3>
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
                                Join Our<br>Emergency Team
                            </h1>
                            <p class="text-white/90 text-lg leading-relaxed">
                                Become part of our first responder network. Register to access training, emergency protocols, and coordinate with fellow firefighters.
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

                <!-- Right Side - Registration Form (SAME STYLE AS LOGIN) -->
                <div class="lg:w-1/2 p-8 lg:p-12 bg-white">
                    <div class="max-w-md mx-auto">
                        <!-- Form Header -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                            <p class="mt-2 text-gray-600">
                                Register as a first responder or administrator
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
<!-- Add this temporarily to debug -->
@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
                        <!-- Registration Form -->
                       <form class="space-y-5" method="POST" action="{{ route('register.store') }}">
                            @csrf
                            <!-- CSRF Token (for Laravel) -->
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                            <!-- Full Name Field -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" id="name" name="name" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                        placeholder="John Smith"
                                        value="{{ old('name') }}">
                                </div>
                            </div>

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
                                        placeholder="john.smith@firewatch.com"
                                        value="{{ old('email') }}">
                                </div>
                            </div>

                            <!-- Role Selection -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                    Account Type
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-helmet-safety text-gray-400"></i>
                                    </div>
                                    <select id="role" name="role" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition appearance-none bg-white">
                                        <option value="" disabled selected>Select your role</option>
                                        <option value="citizen">citizen</option>
                                        {{-- <option value="paramedic">Paramedic</option>
                                        <option value="dispatcher">Emergency Dispatcher</option>
                                        <option value="captain">Station Captain</option>
                                        <option value="admin">Administrator</option> --}}
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            {{-- <!-- Station ID / Badge Number -->
                            <div>
                                <label for="badge_number" class="block text-sm font-medium text-gray-700 mb-1">
                                    Badge Number / Station ID
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-id-card text-gray-400"></i>
                                    </div>
                                    <input type="text" id="badge_number" name="badge_number" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                        placeholder="FIRE-2024-001">
                                </div>
                            </div> --}}

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
                                <p class="mt-1 text-xs text-gray-500">Minimum 8 characters with 1 number and 1 uppercase</p>
                            </div>

                            <!-- Confirm Password Field -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-solid fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password_confirmation" name="password_confirmation" required 
                                        class="pl-10 w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                                        placeholder="••••••••">
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="terms" name="terms" required 
                                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="text-gray-600">
                                        I agree to the 
                                        <a href="#" class="font-medium text-red-600 hover:text-red-500">Terms of Service</a> 
                                        and 
                                        <a href="#" class="font-medium text-red-600 hover:text-red-500">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Emergency Contact Agreement -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="emergency_agreement" name="emergency_agreement" required 
                                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="emergency_agreement" class="text-gray-600">
                                        I confirm that I am a verified emergency responder and will follow all protocols
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" 
                                class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                <i class="fa-solid fa-user-plus"></i>
                                Create Account
                            </button>

                            <!-- Quick Registration Demo (Optional) -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Demo Accounts</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" 
                                    onclick="fillFirefighter()"
                                    class="w-full inline-flex justify-center items-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-user-firefighter text-red-600 mr-2"></i>
                                    Firefighter
                                </button>
                                <button type="button" 
                                    onclick="fillAdmin()"
                                    class="w-full inline-flex justify-center items-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-shield-halved text-red-600 mr-2"></i>
                                    Admin
                                </button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center pt-4">
                                <p class="text-sm text-gray-600">
                                    Already have an account? 
                                    <a href="#" class="font-medium text-red-600 hover:text-red-500">
                                        Sign in here
                                    </a>
                                </p>
                            </div>
                        </form>

                        <!-- Security Notice -->
                        <div class="text-center text-xs text-gray-500 mt-6">
                            <i class="fa-solid fa-shield-halved mr-1"></i>
                            Secured by PyroSense 24/7 Emergency System
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (Simplified) -->
    <footer class="bg-gray-900 text-white py-4">
        <div class="container mx-auto px-4 text-center text-gray-400 text-xs">
            <p>©2024 PyroSense 24/7 Emergency Response System. All rights reserved.</p>
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

        // Fill demo credentials for Firefighter
        function fillFirefighter() {
            document.getElementById('name').value = 'John Firefighter';
            document.getElementById('email').value = 'firefighter@firewatch.com';
            // document.getElementById('role').value = 'firefighter';
            // document.getElementById('badge_number').value = 'FIRE-2024-123';
            document.getElementById('password').value = 'Firefighter123';
            document.getElementById('password_confirmation').value = 'Firefighter123';
            document.getElementById('terms').checked = true;
            document.getElementById('emergency_agreement').checked = true;
            
            // Highlight fields briefly
            highlightFields();
        }

        // Fill demo credentials for Admin
        function fillAdmin() {
            document.getElementById('name').value = 'Sarah Admin';
            document.getElementById('email').value = 'admin@firewatch.com';
            // document.getElementById('role').value = 'admin';
            // document.getElementById('badge_number').value = 'ADMIN-001';
            document.getElementById('password').value = 'Admin123';
            document.getElementById('password_confirmation').value = 'Admin123';
            document.getElementById('terms').checked = true;
            document.getElementById('emergency_agreement').checked = true;
            
            // Highlight fields briefly
            highlightFields();
        }

        // Highlight fields function
     document.querySelector('form').addEventListener('submit', function(e) {
    // Remove e.preventDefault() or call submit after validation
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;

    if(password !== confirmPassword){
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }

    if(!document.getElementById('terms').checked){
        e.preventDefault();
        alert('Please accept the Terms of Service');
        return;
    }

    if(!document.getElementById('emergency_agreement').checked){
        e.preventDefault();
        alert('Please confirm emergency responder status');
        return;
    }

    // Otherwise allow the form to submit
});
    </script>
</body>
</html>