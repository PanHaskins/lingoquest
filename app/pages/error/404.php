<style>
    .error-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: calc(100vh - 110px);
        padding: 2rem;
    }

    .error-code {
        font-size: 8rem;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
        margin: 0;
        animation: float 6s ease-in-out infinite;
    }

    .error-message {
        font-size: 1.5rem;
        color: var(--text);
        margin: 1rem 0 2rem;
    }
    
    @keyframes float {
        0% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
        100% { transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .error-code {
            font-size: 6rem;
        }
        
        .error-message {
            font-size: 1.25rem;
        }
    }
</style>

<div class="error-container">
    <h1 class="error-code">404</h1>
    <p class="error-message"><?php echo __('404')?></p>
    <a href="/" class="btn btn-primary"><?php echo __('back_home')?></a>
</div>