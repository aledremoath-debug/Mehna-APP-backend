 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mahna Backend</title>
    <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container"> 
        <!-- منطقة الشخصيات الكرتونية -->
         <div class="characters-section">
            <div class="characters-container">
                <!-- الشخصية 1 -->
         <div class="character" id="character1">
                    <div class="character-body">    
                        <div class="character-head">
                            <div class="character-face">
                                <div class="character-eyes">
                                    <div class="eye left-eye looking-right"></div>
                                    <div class="eye right-eye looking-right"></div>
                                </div>
                                <div class="character-mouth happy"></div>
                            </div>
                        </div>
                        <div class="character-arms">
                            <div class="arm left-arm"></div>
                            <div class="arm right-arm"></div>
                        </div>
                        <div class="character-legs">
                            <div class="leg left-leg"></div>
                            <div class="leg right-leg"></div>
                        </div>
                    </div>
                </div>
                
                <!-- الشخصية 2 -->
                 <div class="character" id="character2">
                    <div class="character-body">
                        <div class="character-head">
                            <div class="character-face">
                                <div class="character-eyes">
                                    <div class="eye left-eye looking-right"></div>
                                    <div class="eye right-eye looking-right"></div>
                                </div>
                                <div class="character-mouth happy"></div>
                            </div>
                        </div>
                        <div class="character-arms">
                            <div class="arm left-arm"></div>
                            <div class="arm right-arm"></div>
                        </div>
                        <div class="character-legs">
                            <div class="leg left-leg"></div>
                            <div class="leg right-leg"></div>
                        </div>
                    </div>
                </div> 
                
                <!-- الشخصية 3 -->
                 <div class="character" id="character3">
                    <div class="character-body">
                        <div class="character-head">
                            <div class="character-face">
                                <div class="character-eyes">
                                    <div class="eye left-eye looking-right"></div>
                                    <div class="eye right-eye looking-right"></div>
                                </div>
                                <div class="character-mouth happy"></div>
                            </div>
                        </div>
                        <div class="character-arms">
                            <div class="arm left-arm"></div>
                            <div class="arm right-arm"></div>
                        </div>
                        <div class="character-legs">
                            <div class="leg left-leg"></div>
                            <div class="leg right-leg"></div>
                        </div>
                    </div>
                </div>
                
                <!-- الشخصية 4 -->
                 <div class="character" id="character4">
                    <div class="character-body">
                        <div class="character-head">
                            <div class="character-face">
                                <div class="character-eyes">
                                    <div class="eye left-eye looking-right"></div>
                                    <div class="eye right-eye looking-right"></div>
                                </div>
                                <div class="character-mouth happy"></div>
                            </div>
                        </div>
                        <div class="character-arms">
                            <div class="arm left-arm"></div>
                            <div class="arm right-arm"></div>
                        </div>
                        <div class="character-legs">
                            <div class="leg left-leg"></div>
                            <div class="leg right-leg"></div>
                        </div>
                    </div>
                </div>
            </div> 
            
            <!-- كلمة ترحيبية -->
         <div class="characters-title">
                <h2>Welcome to Mahna Admin</h2>
                <p>Our friendly characters are here to assist you!</p>
            </div>
        </div>

        <!-- نموذج تسجيل الدخول --> 
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your admin account</p>
            </div> 

             <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm">
                @csrf
                
                @if ($errors->any())
                    <div class="alert-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif 

                 <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="example@example.com" value="{{ old('email') }}" required autofocus>
                </div> 

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group remember-section">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" id="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    Sign In
                </button>
            </form>
            
            <!-- رسالة تفاعل الشخصيات -->
             <div class="character-message" id="characterMessage">
                <p>Hello! We're happy to see you!</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/admin/login.js') }}"></script>
</body> 
 </html>
 
 

