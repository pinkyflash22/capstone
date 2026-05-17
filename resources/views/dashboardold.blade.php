<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Fire Emergency Response System</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/functions.js'])

</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-fire-extinguisher text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800"> Pyro<span class="text-red-600">Sense</span></span>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Home</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Emergency</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Services</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Resources</a>
                    <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Contact</a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('auth.login') }}" class="text-gray-700 hover:text-red-600">Login</a>
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

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-red-50 to-orange-50 py-12 lg:py-20">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl lg:text-5xl font-bold text-gray-800 mb-4">
                        Find Your <span class="text-red-600">Nearest</span><br>Fire Station
                    </h1>
                    <p class="text-xl text-gray-600 mb-8">
                        Emergency response, fire prevention, and safety resources at your nearest location
                    </p>
                    
                    <!-- Search Box -->
                    <div class="bg-white p-4 rounded-xl shadow-lg mb-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-4 text-gray-400"></i>
                                <input type="text" placeholder="Search nearest fire station here" 
                                       class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500">
                            </div>
                            <button class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-medium">
                                Find Now
                            </button>
                        </div>
                    </div>
                    
                    <!-- Location Button -->
                    <button class="flex items-center gap-2 text-gray-600 hover:text-red-600">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Set your location for emergency alerts</span>
                    </button>
                </div>
                
                <!-- Hero Image/Illustration -->
                <div class="hidden lg:block">
                    <img src="https://img.freepik.com/free-vector/emergency-services-illustration_23-2148870344.jpg" alt="Fire Emergency Services" class="w-full rounded-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
            <h2 class="text-3xl lg:text-4xl font-bold text-center text-gray-800 mb-4">Our Emergency Services</h2>
            <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">
                24/7 rapid response and professional fire protection services for your safety
            </p>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-red-50 p-6 rounded-xl text-center hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-truck text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Rapid Response</h3>
                    <p class="text-gray-600">Emergency dispatch within minutes, 24/7 coverage across all zones</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-orange-50 p-6 rounded-xl text-center hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-user text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Fire Prevention</h3>
                    <p class="text-gray-600">Regular inspections, safety audits, and prevention programs</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-yellow-50 p-6 rounded-xl text-center hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-kit-medical text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Emergency Medical</h3>
                    <p class="text-gray-600">First response medical assistance and rescue operations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Equipment Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 lg:px-8">
            <h2 class="text-3xl lg:text-4xl font-bold text-center text-gray-800 mb-4">Emergency Equipment</h2>
            <p class="text-gray-600 text-center mb-8">Browse our emergency response equipment by category</p>
            
            <!-- Categories -->
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button class="bg-red-600 text-white px-6 py-2 rounded-full">All Equipment</button>
                <button class="bg-white text-gray-700 px-6 py-2 rounded-full hover:bg-red-50">Fire Extinguishers</button>
                <button class="bg-white text-gray-700 px-6 py-2 rounded-full hover:bg-red-50">Hoses & Nozzles</button>
                <button class="bg-white text-gray-700 px-6 py-2 rounded-full hover:bg-red-50">Protective Gear</button>
                <button class="bg-white text-gray-700 px-6 py-2 rounded-full hover:bg-red-50">Rescue Tools</button>
                <button class="bg-white text-gray-700 px-6 py-2 rounded-full hover:bg-red-50">Alarm Systems</button>
            </div>
            
            <!-- Equipment Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Item 1 -->
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition">
                    <div class="h-48 bg-gray-200 relative">
                        <img src="https://img.freepik.com/free-photo/fire-extinguisher-red-color_74190-7987.jpg" alt="Fire Extinguisher" class="w-full h-full object-cover">
                        <span class="absolute top-2 right-2 bg-red-600 text-white px-2 py-1 rounded text-sm">Emergency</span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg">ABC Fire Extinguisher</h3>
                        <p class="text-gray-600 text-sm mb-2">Multi-purpose dry chemical</p>
                        <p class="text-red-600 font-bold text-xl">$89.99</p>
                        <button class="mt-3 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Request</button>
                    </div>
                </div>
                
                <!-- Item 2 -->
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition">
                    <div class="h-48 bg-gray-200">
                        <img src="https://img.freepik.com/free-photo/firefighter-holding-hose_23-2148870350.jpg" alt="Fire Hose" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg">Professional Fire Hose</h3>
                        <p class="text-gray-600 text-sm mb-2">1.5 inch, 50ft length</p>
                        <p class="text-red-600 font-bold text-xl">$159.99</p>
                        <button class="mt-3 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Request</button>
                    </div>
                </div>
                
                <!-- Item 3 -->
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition">
                    <div class="h-48 bg-gray-200">
                        <img src="https://img.freepik.com/free-photo/firefighter-uniform_23-2148870349.jpg" alt="Firefighter Gear" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg">Firefighter Helmet</h3>
                        <p class="text-gray-600 text-sm mb-2">Heat-resistant, with visor</p>
                        <p class="text-red-600 font-bold text-xl">$79.99</p>
                        <button class="mt-3 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Request</button>
                    </div>
                </div>
                
                <!-- Item 4 -->
                <div class="bg-white rounded-xl overflow-hidden shadow hover:shadow-lg transition">
                    <div class="h-48 bg-gray-200">
                        <img src="https://img.freepik.com/free-photo/smoke-detector_1048-1380.jpg" alt="Smoke Detector" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg">Smart Smoke Alarm</h3>
                        <p class="text-gray-600 text-sm mb-2">WiFi enabled, app alerts</p>
                        <p class="text-red-600 font-bold text-xl">$49.99</p>
                        <button class="mt-3 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Request</button>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-8">
                <button class="bg-white text-red-600 border-2 border-red-600 px-8 py-3 rounded-lg hover:bg-red-600 hover:text-white transition font-medium">
                    View More Equipment
                </button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-red-600">
        <div class="container mx-auto px-4 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
                STAY PROTECTED FROM FIRE<br>EMERGENCIES ANYTIME & ANYWHERE
            </h2>
            <p class="text-red-100 mb-8 max-w-2xl mx-auto">
                Download our mobile app for instant emergency alerts, fire station locations, and safety tips
            </p>
            <button class="bg-white text-red-600 px-8 py-3 rounded-lg font-bold hover:bg-red-50 transition">
                Get Started with PyroSense
            </button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
                <!-- Contact Us -->
                <div>
                    <h4 class="font-bold mb-4">Emergency Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-phone-alt text-red-500"></i>
                            911 (Emergency)
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-red-500"></i>
                            +62 21-3092-1234
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-envelope text-red-500"></i>
                            pyrosense@emergency.com
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-red-500"></i>
                            Pangasinan, Philippines
                        </li>
                    </ul>
                </div>
                
                <!-- About Us -->
                <div>
                    <h4 class="font-bold mb-4">About Us</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>History</li>
                        <li>Career</li>
                        <li>FAQ</li>
                        <li>News & Events</li>
                    </ul>
                </div>
                
                <!-- Connect With Us -->
                <div>
                    <h4 class="font-bold mb-4">Connect With Us</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-facebook text-blue-500"></i>
                            Facebook
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-twitter text-blue-400"></i>
                            Twitter
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-instagram text-pink-600"></i>
                            Instagram
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-brands fa-linkedin text-blue-700"></i>
                            LinkedIn
                        </li>
                    </ul>
                </div>
                
                <!-- Location -->
                <div>
                    <h4 class="font-bold mb-4">Our Locations</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Fire Station 1 - Urbiztondo</li>
                        <li>Fire Station 2 - Mangatarem</li>
                        <li>Fire Station 3 - San Carlos</li>
                        <li>Headquarters - Lingayen 21 Lt 11</li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-gray-800 my-8">
            
            <div class="text-center text-gray-500">
                <p>All Rights Reserved ©2026. Designed By PyroSense Emergency Response System</p>
            </div>
        </div>
    </footer>
</body>
</html>
