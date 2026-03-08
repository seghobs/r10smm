<style>
    :root {
        --primary: #8B5CF6;
        --primary-dark: #7C3AED;
        --secondary: #10B981;
        --accent: #F59E0B;
        --bg-body: #020617;
        --bg-card: rgba(30, 41, 59, 0.6);
        --text-main: #F8FAFC;
        --text-muted: #94A3B8;
        --gradient-main: linear-gradient(135deg, #8B5CF6 0%, #4F46E5 100%);
        --gradient-text: linear-gradient(135deg, #C4B5FD 0%, #6EE7B7 100%);
        --glass-border: 1px solid rgba(255, 255, 255, 0.08);
        --glow: 0 0 30px rgba(139, 92, 246, 0.3);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; scroll-behavior: smooth; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-main); overflow-x: hidden; line-height: 1.6; }

    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg-body); }
    ::-webkit-scrollbar-thumb { background: var(--primary-dark); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

    .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; pointer-events: none; }
    .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; animation: float 15s infinite alternate ease-in-out; }
    .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
    .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; animation-delay: -5s; }
    .blob-3 { top: 40%; left: 30%; width: 300px; height: 300px; background: var(--accent); opacity: 0.15; animation-delay: -8s; }

    @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 40px) scale(1.05); } }

    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

    .navbar { padding: 20px 0; position: fixed; width: 100%; top: 0; z-index: 1000; background: rgba(2, 6, 23, 0.7); backdrop-filter: blur(15px); border-bottom: var(--glass-border); transition: 0.3s; }
    .navbar.scrolled { padding: 15px 0; background: rgba(2, 6, 23, 0.95); }
    
    .nav-inner { display: flex; justify-content: space-between; align-items: center; height: 50px; }
    
    .logo { 
        display: flex; align-items: center; gap: 12px; font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; 
        text-decoration: none; color: white; letter-spacing: -0.5px; margin-right: 40px; flex-shrink: 0;
    }
    .logo i { color: var(--primary); font-size: 1.8rem; filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5)); }
    
    .nav-links { display: flex; gap: 25px; align-items: center; margin-right: auto; }
    .nav-links a { text-decoration: none; color: var(--text-muted); font-weight: 500; transition: 0.3s; font-size: 0.95rem; position: relative; white-space: nowrap; }
    .nav-links a:hover, .nav-links a.active { color: white; }
    .nav-links a.active::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 100%; height: 2px; background: var(--gradient-main); border-radius: 2px; }
    
    .nav-actions { display: flex; gap: 15px; flex-shrink: 0; }
    
    .btn { padding: 10px 24px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; font-size: 0.95rem; letter-spacing: 0.3px; }
    .btn-primary { background: var(--gradient-main); color: white; box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); border: 1px solid rgba(255,255,255,0.1); }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(139, 92, 246, 0.6); }
    .btn-outline { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.15); color: white; backdrop-filter: blur(5px); }
    .btn-outline:hover { background: rgba(255,255,255,0.1); border-color: white; transform: translateY(-3px); }

    .menu-toggle { display: none; font-size: 1.5rem; color: white; cursor: pointer; }

    .footer { padding: 60px 0 30px; background: rgba(2, 6, 23, 0.95); border-top: var(--glass-border); margin-top: 100px; }
    .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 50px; margin-bottom: 60px; }
    .footer-col h4 { color: white; margin-bottom: 25px; font-size: 1.2rem; font-weight: 700; font-family: 'Outfit', sans-serif; }
    .footer-links a { display: block; color: var(--text-muted); text-decoration: none; margin-bottom: 12px; transition: 0.3s; font-size: 0.95rem; }
    .footer-links a:hover { color: var(--primary); transform: translateX(5px); }
    .social-icons { display: flex; gap: 10px; margin-top: 25px; }
    .social-icons a { display: inline-flex; width: 45px; height: 45px; background: rgba(255,255,255,0.05); border-radius: 12px; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s; border: 1px solid rgba(255,255,255,0.05); }
    .social-icons a:hover { background: var(--primary); transform: translateY(-5px); border-color: transparent; box-shadow: var(--glow); }

    /* Auth Pages Styles */
    .auth-container { width: 100%; max-width: 500px; position: relative; z-index: 10; margin: 50px auto; }
    .auth-card {
        background: var(--bg-card); backdrop-filter: blur(20px); border: var(--glass-border);
        padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .logo-area { text-align: center; margin-bottom: 30px; }
    .auth-desc { color: var(--text-muted); margin-top: 5px; font-size: 0.95rem; text-align: center; }
    .form-group { margin-bottom: 20px; position: relative; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.85rem; color: var(--text-muted); }
    .input-wrapper { position: relative; }
    .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); transition: 0.3s; }
    .form-control {
        width: 100%; padding: 12px 15px 12px 45px; background: rgba(255, 255, 255, 0.03);
        border: var(--glass-border); border-radius: 12px; color: white; font-size: 0.95rem; transition: 0.3s;
    }
    .form-control:focus { outline: none; border-color: var(--primary); background: rgba(255, 255, 255, 0.07); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .back-home { 
        position: absolute; top: 100px; left: 30px; color: var(--text-muted); text-decoration: none; 
        display: flex; align-items: center; gap: 8px; font-weight: 600; transition: 0.3s; z-index: 20; 
        padding: 10px 20px; background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255,255,255,0.1); 
        border-radius: 50px; backdrop-filter: blur(5px); 
    }
    .back-home:hover { color: white; border-color: var(--primary); transform: translateX(-5px); }

    .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
    .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; }
    .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6EE7B7; }

    /* Hero & Section Styles */
    .hero-section { padding: 200px 0 120px; text-align: center; position: relative; }
    .hero-title { font-family: 'Outfit', sans-serif; font-size: 5rem; line-height: 1.1; font-weight: 800; margin-bottom: 25px; letter-spacing: -2px; }
    .hero-desc { color: var(--text-muted); font-size: 1.3rem; max-width: 700px; margin: 0 auto 50px; line-height: 1.7; }
    .text-gradient { background: var(--gradient-text); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    
    .stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-top: 100px; }
    .stat-item, .stat-box { background: var(--bg-card); backdrop-filter: blur(15px); padding: 30px 20px; border-radius: 24px; border: var(--glass-border); text-align: center; transition: 0.4s; }
    .stat-number, .stat-num { display: block; font-size: 3rem; font-weight: 800; color: white; margin-bottom: 5px; font-family: 'Outfit', sans-serif; }
    .stat-label, .stat-lbl { color: var(--text-muted); font-weight: 500; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }

    .section-title { text-align: center; font-size: 3rem; font-weight: 800; margin-bottom: 80px; font-family: 'Outfit', sans-serif; letter-spacing: -1px; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
    .feature-card { background: var(--bg-card); border: var(--glass-border); padding: 50px 40px; border-radius: 30px; transition: 0.4s; }
    .feature-icon { width: 70px; height: 70px; background: rgba(139, 92, 246, 0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #C4B5FD; margin-bottom: 30px; }

    /* Animations */
    .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease; }
    .reveal.active { opacity: 1; transform: translateY(0); }

    @media (max-width: 992px) {
        .hero-title { font-size: 3.5rem; }
        .stats-bar { grid-template-columns: 1fr 1fr; }
        .nav-links { 
            display: none; position: fixed; top: 70px; left: 0; width: 100%; background: rgba(2, 6, 23, 0.98); 
            flex-direction: column; padding: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(20px); 
            height: auto; margin-right: 0; align-items: stretch; text-align: center;
        }
        .nav-links.active { display: flex; }
        .nav-links a { padding: 10px 0; font-size: 1.1rem; }
        .nav-actions { display: none; }
        .menu-toggle { display: block; }
        .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    
    @media (max-width: 576px) {
        .footer-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="background-glow">
    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>
    <div class="glow-blob blob-3"></div>
</div>
