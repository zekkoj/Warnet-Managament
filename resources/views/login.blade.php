<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warnet Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
            overflow: hidden;
            transition: background 1s ease;
        }
        
        /* Time-based backgrounds */
        .bg-morning {
            background: linear-gradient(135deg, #ff9a56 0%, #ffcd67 30%, #87ceeb 70%, #1e90ff 100%);
        }
        
        .bg-day {
            background: linear-gradient(135deg, #56ccf2 0%, #2f80ed 50%, #1e3c72 100%);
        }
        
        .bg-evening {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 25%, #f093fb 50%, #4a00e0 100%);
        }
        
        .bg-night {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        }
        
        /* Weather particles container */
        .weather-effect {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        /* Sun element */
        .sun {
            position: fixed;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: radial-gradient(circle, #fff9c4 0%, #ffeb3b 50%, #ff9800 100%);
            box-shadow: 0 0 60px #ffeb3b, 0 0 100px #ff9800;
            animation: pulse 3s ease-in-out infinite;
            z-index: 2;
        }
        
        /* Moon element */
        .moon {
            position: fixed;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #f5f5f5 0%, #e0e0e0 50%, #bdbdbd 100%);
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.5), 0 0 80px rgba(255, 255, 255, 0.3);
            z-index: 2;
        }
        
        .moon::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 15px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: rgba(0,0,0,0.1);
        }
        
        .moon::after {
            content: '';
            position: absolute;
            top: 35px;
            left: 35px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(0,0,0,0.08);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
        
        /* Stars for night */
        .star {
            position: absolute;
            width: 3px;
            height: 3px;
            background: white;
            border-radius: 50%;
            animation: twinkle 2s ease-in-out infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* Clouds */
        .cloud {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            animation: float-cloud 20s linear infinite;
        }
        
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
        }
        
        @keyframes float-cloud {
            0% { transform: translateX(-200px); }
            100% { transform: translateX(calc(100vw + 200px)); }
        }
        
        /* Birds for morning/day */
        .bird {
            position: absolute;
            font-size: 12px;
            color: #333;
            animation: fly 15s linear infinite;
        }
        
        @keyframes fly {
            0% { transform: translateX(-50px) translateY(0); }
            25% { transform: translateX(25vw) translateY(-20px); }
            50% { transform: translateX(50vw) translateY(10px); }
            75% { transform: translateX(75vw) translateY(-15px); }
            100% { transform: translateX(calc(100vw + 50px)) translateY(0); }
        }
        
        /* Glassmorphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.2);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .login-btn {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.15) 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Time display */
        .time-display {
            font-size: 5rem;
            font-weight: 200;
            letter-spacing: -5px;
            text-shadow: 0 0 40px rgba(255, 255, 255, 0.3);
        }
        
        /* Weather icon animation */
        .weather-icon {
            font-size: 2rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4" id="bodyElement">
    <!-- Weather Effects Container -->
    <div class="weather-effect" id="weatherEffect"></div>
    
    <!-- Sun/Moon Element -->
    <div id="celestialBody"></div>
    
    <!-- Main Container -->
    <div class="relative z-10 flex w-full max-w-5xl h-[600px] glass-card rounded-3xl overflow-hidden">
        
        <!-- Left Side - Branding -->
        <div class="hidden md:flex flex-col justify-between w-1/2 p-10 relative overflow-hidden">
            <!-- Background overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-transparent to-black/20"></div>
            
            <!-- Logo -->
            <div class="relative z-10">
                <h2 class="text-white/90 text-lg font-light tracking-widest">warnet.ms</h2>
            </div>
            
            <!-- Time & Info -->
            <div class="relative z-10">
                <div class="time-display text-white" id="currentTime">00:00</div>
                <div class="flex items-center gap-3 mt-2">
                    <span class="text-3xl text-white font-light">Warnet</span>
                    <span class="weather-icon text-white/80" id="weatherIcon">
                        <i class="fas fa-sun"></i>
                    </span>
                </div>
                <p class="text-white/70 text-sm mt-1" id="currentDate">Loading...</p>
                <div class="flex items-center gap-2 mt-3">
                    <span class="inline-block px-4 py-1 rounded-full bg-white/20 text-white/90 text-sm" id="timeGreeting">
                        <i class="fas fa-gamepad mr-2"></i>Selamat Datang
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="w-full md:w-1/2 p-10 flex flex-col justify-center bg-gradient-to-br from-slate-800/50 to-slate-900/50">
            
            <!-- Search icon decoration -->
            <div class="absolute top-6 right-6">
                <div class="w-10 h-10 rounded-lg bg-[#aec2c7]/20 flex items-center justify-center">
                    <i class="fas fa-user text-[#aec2c7]/70"></i>
                </div>
            </div>
            
            <!-- Welcome text -->
            <div class="mb-8">
                <h1 class="text-white text-2xl font-semibold mb-2">Welcome Back</h1>
                <p class="text-white/50 text-sm">Sign in to continue to dashboard</p>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" class="space-y-5">
                <div>
                    <label class="block text-white/60 text-sm mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
                        <input type="text" id="username" value="Zzzzzz" required
                               placeholder="Enter your username"
                               class="glass-input w-full pl-12 pr-4 py-4 rounded-xl text-white outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white/60 text-sm mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
                        <input type="password" id="password" value="66666666" required
                               placeholder="Enter your password"
                               class="glass-input w-full pl-12 pr-12 py-4 rounded-xl text-white outline-none">
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center text-white/50 cursor-pointer">
                        <input type="checkbox" class="mr-2 accent-[#aec2c7]">
                        Remember me
                    </label>
                    <a href="#" class="text-[#aec2c7]/70 hover:text-[#aec2c7] transition">Forgot password?</a>
                </div>
                
                <button type="submit" id="loginBtn"
                        class="login-btn w-full py-4 rounded-xl text-white font-medium mt-6">
                    <span id="loginText">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </span>
                </button>
            </form>
            
            <!-- Error Message -->
            <div id="error" class="mt-4 p-4 bg-red-500/90 border border-red-400 text-white rounded-xl backdrop-blur-sm opacity-0 transition-opacity duration-300 pointer-events-none" style="display: none;">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span id="errorText"></span>
            </div>
            
            <!-- Weather-like details -->
            <div class="mt-8 pt-6 border-t border-white/10">
                <h3 class="text-white/60 text-sm mb-4">System Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white/40">Status</span>
                        <span class="text-[#aec2c7]"><i class="fas fa-circle text-green-400 text-xs mr-1"></i> Online</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/40">PCs</span>
                        <span class="text-white/70">45 Units</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/40">Server</span>
                        <span class="text-white/70">Active</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/40">Version</span>
                        <span class="text-white/70">v1.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============== TIME-BASED THEME ==============
        function getTimeOfDay() {
            const hour = new Date().getHours();
            if (hour >= 5 && hour < 10) return 'morning';      // 05:00 - 09:59 Pagi
            if (hour >= 10 && hour < 16) return 'day';         // 10:00 - 15:59 Siang
            if (hour >= 16 && hour < 19) return 'evening';     // 16:00 - 18:59 Sore
            return 'night';                                     // 19:00 - 04:59 Malam
        }
        
        function applyTimeTheme() {
            const timeOfDay = getTimeOfDay();
            const body = document.getElementById('bodyElement');
            const weatherIcon = document.getElementById('weatherIcon');
            const timeGreeting = document.getElementById('timeGreeting');
            const celestialBody = document.getElementById('celestialBody');
            const weatherEffect = document.getElementById('weatherEffect');
            
            // Clear previous effects
            body.className = 'flex items-center justify-center p-4';
            weatherEffect.innerHTML = '';
            celestialBody.className = '';
            celestialBody.style = '';
            
            switch(timeOfDay) {
                case 'morning':
                    body.classList.add('bg-morning');
                    weatherIcon.innerHTML = '<i class="fas fa-cloud-sun"></i>';
                    timeGreeting.innerHTML = '<i class="fas fa-coffee mr-2"></i>Selamat Pagi';
                    createSun(15, 20);
                    createClouds(3);
                    createBirds(5);
                    break;
                    
                case 'day':
                    body.classList.add('bg-day');
                    weatherIcon.innerHTML = '<i class="fas fa-sun"></i>';
                    timeGreeting.innerHTML = '<i class="fas fa-sun mr-2"></i>Selamat Siang';
                    createSun(10, 15);
                    createClouds(4);
                    break;
                    
                case 'evening':
                    body.classList.add('bg-evening');
                    weatherIcon.innerHTML = '<i class="fas fa-cloud-sun"></i>';
                    timeGreeting.innerHTML = '<i class="fas fa-mug-hot mr-2"></i>Selamat Sore';
                    createSun(70, 60);
                    createClouds(2);
                    break;
                    
                case 'night':
                    body.classList.add('bg-night');
                    weatherIcon.innerHTML = '<i class="fas fa-moon"></i>';
                    timeGreeting.innerHTML = '<i class="fas fa-moon mr-2"></i>Selamat Malam';
                    createMoon();
                    createStars(50);
                    break;
            }
        }
        
        function createSun(top, left) {
            const celestialBody = document.getElementById('celestialBody');
            celestialBody.className = 'sun';
            celestialBody.style.top = top + '%';
            celestialBody.style.left = left + '%';
        }
        
        function createMoon() {
            const celestialBody = document.getElementById('celestialBody');
            celestialBody.className = 'moon';
            celestialBody.style.top = '15%';
            celestialBody.style.right = '20%';
        }
        
        function createStars(count) {
            const weatherEffect = document.getElementById('weatherEffect');
            for (let i = 0; i < count; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 60 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                star.style.width = (Math.random() * 2 + 1) + 'px';
                star.style.height = star.style.width;
                weatherEffect.appendChild(star);
            }
        }
        
        function createClouds(count) {
            const weatherEffect = document.getElementById('weatherEffect');
            for (let i = 0; i < count; i++) {
                const cloud = document.createElement('div');
                cloud.className = 'cloud';
                cloud.style.width = (Math.random() * 100 + 80) + 'px';
                cloud.style.height = (Math.random() * 30 + 20) + 'px';
                cloud.style.top = (Math.random() * 30 + 5) + '%';
                cloud.style.left = '-200px';
                cloud.style.animationDelay = (i * 5) + 's';
                cloud.style.animationDuration = (Math.random() * 10 + 20) + 's';
                cloud.style.opacity = Math.random() * 0.3 + 0.5;
                weatherEffect.appendChild(cloud);
            }
        }
        
        function createBirds(count) {
            const weatherEffect = document.getElementById('weatherEffect');
            for (let i = 0; i < count; i++) {
                const bird = document.createElement('div');
                bird.className = 'bird';
                bird.innerHTML = '🐦';
                bird.style.top = (Math.random() * 25 + 10) + '%';
                bird.style.left = '-50px';
                bird.style.animationDelay = (i * 3) + 's';
                bird.style.animationDuration = (Math.random() * 5 + 12) + 's';
                weatherEffect.appendChild(bird);
            }
        }
        
        // Apply theme on load
        applyTimeTheme();
        // Update theme every minute
        setInterval(applyTimeTheme, 60000);
        
        // Update time
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('currentTime').textContent = `${hours}:${minutes}`;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', options);
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        // Get redirect URL from query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get('redirect') || '/';
        const isLogout = urlParams.get('logout') === '1';

        // Jika dari logout, clear storage
        if (isLogout) {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
        }

        // Check if already logged in (hanya jika bukan dari logout)
        if (!isLogout && localStorage.getItem('api_token')) {
            window.location.href = redirectUrl;
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');

            // Hide previous error
            errorDiv.style.display = 'none';
            errorDiv.style.opacity = '0';

            // Show loading state
            loginBtn.disabled = true;
            loginText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing in...';

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();
                console.log('Login response:', data);

                if (data.success && data.data && data.data.token) {
                    // Store credentials
                    localStorage.setItem('api_token', data.data.token);
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    
                    // Redirect immediately to dashboard
                    window.location.href = '/';
                } else {
                    loginBtn.disabled = false;
                    loginText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
                    document.getElementById('errorText').textContent = data.message || 'Username atau password salah';
                    showError();
                }
            } catch (error) {
                loginBtn.disabled = false;
                loginText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
                document.getElementById('errorText').textContent = 'Koneksi error. Pastikan server berjalan.';
                showError();
                console.error('Login error:', error);
            }
        });
        
        // Show error with fade in/out animation
        function showError() {
            const errorDiv = document.getElementById('error');
            
            // Show and fade in
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.opacity = '1';
            }, 10);
            
            // Fade out after 1 second
            setTimeout(() => {
                errorDiv.style.opacity = '0';
                // Hide after fade out complete
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 300);
            }, 1000);
        }
    </script>
</body>
</html>
