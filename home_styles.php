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

    .input-wrapper i.toggle-password { left: auto; right: 15px; cursor: pointer; color: var(--text-muted); z-index: 5; }
    
    .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 0.9rem; }
    .custom-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-muted); position: relative; padding-left: 28px; user-select: none; }
    .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
    .checkmark { position: absolute; top: 0; left: 0; height: 20px; width: 20px; background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; transition: 0.2s; }
    .custom-checkbox:hover input ~ .checkmark { background-color: rgba(255,255,255,0.1); }
    .custom-checkbox input:checked ~ .checkmark { background-color: var(--primary); border-color: var(--primary); }
    .checkmark:after { content: ""; position: absolute; display: none; left: 7px; top: 3px; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    .custom-checkbox input:checked ~ .checkmark:after { display: block; }
    .forgot-link { color: var(--primary); text-decoration: none; font-weight: 500; transition: 0.3s; }
    .forgot-link:hover { color: white; text-decoration: underline; }

    .legal-box { margin-bottom: 25px; display: flex; flex-direction: column; gap: 10px; font-size: 0.85rem; }
    .legal-box a { color: var(--primary); text-decoration: none; }
    .legal-box a:hover { text-decoration: underline; }

    .btn-submit { width: 100%; padding: 14px; background: var(--gradient-main); border: none; border-radius: 12px; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-bottom: 25px; box-shadow: var(--glow); display: flex; justify-content: center; align-items: center; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5); }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

    .divider { text-align: center; position: relative; margin-bottom: 25px; }
    .divider::before { content: ''; position: absolute; left: 0; top: 50%; width: 100%; height: 1px; background: rgba(255,255,255,0.1); }
    .divider span { background: #1e293b; padding: 0 15px; position: relative; color: var(--text-muted); font-size: 0.9rem; }

    .social-login { display: flex; gap: 15px; margin-bottom: 25px; }
    .social-btn { flex: 1; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; transition: 0.3s; }
    .social-btn:hover { background: rgba(255,255,255,0.1); transform: translateY(-3px); }

    .auth-footer { text-align: center; color: var(--text-muted); font-size: 0.9rem; }
    .auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; transition: 0.3s; }
    .auth-footer a:hover { color: white; }

    .popup-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .popup-card { background: var(--bg-card); padding: 40px; border-radius: 24px; border: var(--glass-border); text-align: center; max-width: 400px; width: 90%; }
    .popup-icon { width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 20px; }
    .popup-title { font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 10px; }
    .popup-text { color: var(--text-muted); margin-bottom: 20px; font-size: 0.95rem; }
    .api-key-box { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.2); font-family: monospace; color: var(--accent); margin-bottom: 25px; word-break: break-all; }
    .countdown { color: var(--text-muted); font-size: 0.9rem; }

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

    /* Page specific styles (TOS, Privacy, FAQ, Contact) */
    .tos-hero, .contact-hero { padding: 180px 0 80px; text-align: center; }
    .tos-content, .faq-content { padding: 40px 0 100px; }
    .section-header { text-align: center; margin-bottom: 50px; }
    .section-header h2 { font-size: 2.5rem; color: white; margin-bottom: 10px; font-family: 'Outfit', sans-serif; font-weight: 700; }
    .section-header p { color: var(--text-muted); }
    
    .acceptance-box, .terms-box, .warning-box, .info-box { background: var(--bg-card); border: var(--glass-border); padding: 30px; border-radius: 20px; margin-bottom: 25px; backdrop-filter: blur(10px); }
    .acceptance-box { border-left: 4px solid var(--primary); }
    .warning-box { background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-left: 4px solid #ef4444; }
    .info-box { background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-left: 4px solid #3b82f6; }
    
    .acceptance-box h3, .acceptance-box h4, .terms-box h3, .terms-box h4, .warning-box h3, .warning-box h4, .info-box h3, .info-box h4 { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 600; color: white; }
    .acceptance-box p, .terms-box p, .warning-box p, .info-box p { color: var(--text-muted); line-height: 1.6; margin-bottom: 15px; font-size: 0.95rem; }
    .acceptance-box p:last-child, .terms-box p:last-child, .warning-box p:last-child, .info-box p:last-child { margin-bottom: 0; }
    .warning-box ul, .info-box ul { color: var(--text-muted); line-height: 1.6; padding-left: 20px; list-style-type: disc; }
    .warning-box ul li, .info-box ul li { margin-bottom: 8px; }

    /* Contact Page */
    .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 100px; align-items: start; }
    .contact-info { display: flex; flex-direction: column; gap: 20px; }
    .contact-card { background: var(--bg-card); border: var(--glass-border); padding: 25px; border-radius: 20px; display: flex; gap: 20px; align-items: start; backdrop-filter: blur(10px); transition: 0.3s; }
    .contact-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.1); }
    .contact-icon { width: 50px; height: 50px; background: rgba(139, 92, 246, 0.1); color: var(--primary); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .contact-details h3 { margin-bottom: 10px; color: white; font-weight: 600; }
    .contact-details p { color: var(--text-muted); margin-bottom: 15px; font-size: 0.9rem; line-height: 1.5; }
    .contact-link { color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; transition: 0.3s; font-size: 0.95rem; }
    .contact-link:hover { color: white; transform: translateX(5px); }
    .form-container { background: var(--bg-card); border: var(--glass-border); padding: 40px; border-radius: 24px; backdrop-filter: blur(10px); }
    .form-header h2 { margin-bottom: 10px; color: white; font-size: 2rem; font-family: 'Outfit', sans-serif; }
    .form-header p { color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; }
    .form-container textarea.form-control { min-height: 120px; resize: vertical; }
    
    .btn { padding: 12px 30px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; font-size: 0.95rem; }
    .btn-primary { background: var(--gradient-main); color: white; }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: var(--glow); color: white; }
    .btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; }
    .btn-outline:hover { background: rgba(255,255,255,0.05); border-color: white; transform: translateY(-3px); }

    /* FAQ Page */
    .search-box { max-width: 600px; margin: 0 auto 40px; position: relative; }
    .search-box i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
    .search-box input { width: 100%; padding: 18px 20px 18px 50px; border-radius: 50px; background: rgba(255,255,255,0.05); border: var(--glass-border); color: white; font-size: 1rem; transition: 0.3s; backdrop-filter: blur(10px); }
    .search-box input:focus { border-color: var(--primary); outline: none; background: rgba(255,255,255,0.08); box-shadow: 0 0 15px rgba(139, 92, 246, 0.2); }
    .faq-categories { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
    .cat-btn { padding: 10px 20px; border-radius: 50px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); cursor: pointer; transition: 0.3s; font-weight: 500; backdrop-filter: blur(5px); }
    .cat-btn:hover { background: rgba(255,255,255,0.1); color: white; }
    .cat-btn.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: var(--glow); }
    .faq-grid { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
    .faq-item { background: var(--bg-card); border: var(--glass-border); border-radius: 16px; overflow: hidden; backdrop-filter: blur(10px); transition: 0.3s; }
    .faq-question { padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; color: white; font-weight: 600; font-size: 1rem; user-select: none; }
    .faq-question i { transition: 0.3s; color: var(--text-muted); }
    .faq-answer { padding: 0 25px; max-height: 0; overflow: hidden; transition: all 0.4s cubic-bezier(0, 1, 0, 1); color: var(--text-muted); line-height: 1.6; font-size: 0.95rem; }
    .faq-item.active { border-color: rgba(139, 92, 246, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .faq-item.active .faq-answer { padding: 0 25px 20px; max-height: 1000px; transition: all 0.5s ease-in-out; }
    .faq-item.active .faq-question i { transform: rotate(180deg); color: var(--primary); }
    .help-box { max-width: 800px; margin: 60px auto 0; background: var(--gradient-card); border: var(--glass-border); padding: 40px; border-radius: 24px; text-align: center; }
    .help-box h3 { color: white; margin-bottom: 15px; font-size: 1.5rem; font-weight: 700; font-family: 'Outfit', sans-serif; }
    .help-box p { color: var(--text-muted); margin-bottom: 25px; }

    /* About Page Styles */
    .about-hero { padding: 180px 0 80px; text-align: center; }
    .content-section { padding: 40px 0 100px; }
    .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 80px; align-items: center; }
    .about-text h2 { font-size: 2.5rem; color: white; margin-bottom: 25px; font-family: 'Outfit', sans-serif; font-weight: 700; }
    .about-text p { color: var(--text-muted); line-height: 1.7; margin-bottom: 20px; font-size: 1.05rem; }
    .about-card { background: var(--bg-card); border: var(--glass-border); padding: 40px; border-radius: 24px; backdrop-filter: blur(10px); }
    .values-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 80px; text-align: center; }
    .value-item { background: var(--bg-card); border: var(--glass-border); padding: 40px 30px; border-radius: 20px; backdrop-filter: blur(10px); transition: 0.3s; }
    .value-item:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.3); border-color: rgba(139, 92, 246, 0.3); }
    .value-icon { width: 70px; height: 70px; background: rgba(139, 92, 246, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
    .value-item h3 { color: white; margin-bottom: 15px; font-size: 1.3rem; font-weight: 600; }
    .value-item p { color: var(--text-muted); line-height: 1.6; }

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
        .contact-grid { grid-template-columns: 1fr; }
        .about-grid { grid-template-columns: 1fr; }
        .values-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 576px) {
        .footer-grid, .form-row { grid-template-columns: 1fr; gap: 30px; }
        .tos-hero, .contact-hero, .about-hero, .hero-section { padding: 120px 0 40px; }
        .hero-title { font-size: 2.2rem; margin-bottom: 20px; line-height: 1.2; }
        .hero-desc { font-size: 1.1rem; padding: 0 10px; }
        
        .stats-bar { grid-template-columns: 1fr; gap: 15px; margin-top: 50px; }
        .stat-item { padding: 25px 15px; }
        
        .section-title { font-size: 2rem; margin-bottom: 40px; }
        .feature-card { padding: 30px 25px; }
        
        /* Typography overrides */
        h2 { font-size: 1.8rem !important; }
        h3 { font-size: 1.3rem !important; }
        
        /* Component spacing */
        .container { padding: 0 15px; }
        .auth-card { padding: 25px; border-radius: 20px; }
        .form-container { padding: 25px; }
        
        /* Navbar App Feel */
        .navbar { padding: 12px 0; }
        .logo { font-size: 1.2rem; }
        .logo i { font-size: 1.4rem; }
        
        /* Button touches */
        .btn { width: 100%; justify-content: center; } /* Full width buttons on text-heavy mobile sections */
    }
</style>
<div class="background-glow">
    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>
    <div class="glow-blob blob-3"></div>
</div>
