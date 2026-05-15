(() => {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

    const menuButton = $('[data-menu-button]');
    const menu = $('[data-menu]');
    if (menuButton && menu) {
        menuButton.addEventListener('click', () => {
            menu.classList.toggle('open');
            menuButton.classList.toggle('open');
        });
    }

    $$('[data-close-alert]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-alert]')?.remove());
    });

    setTimeout(() => {
        $$('[data-alert]').forEach((alert) => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        });
    }, 7000);

    $$('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Ocultar' : 'Mostrar';
        });
    });

    $$('[data-uppercase]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase().replace(/\s/g, '');
        });
    });

    const senha = $('[data-password-strength]');
    const barra = $('[data-password-bar]');
    const texto = $('[data-password-text]');
    const confirmarSenha = $('input[name="confirmar_senha"]');

    const avaliarSenha = (valor) => {
        let pontos = 0;
        if (valor.length >= 8) pontos++;
        if (/[a-z]/.test(valor)) pontos++;
        if (/[A-Z]/.test(valor)) pontos++;
        if (/\d/.test(valor)) pontos++;
        if (/[^A-Za-z0-9]/.test(valor)) pontos++;
        return pontos;
    };

    const atualizarForcaSenha = () => {
        if (!senha || !barra || !texto) return;
        const pontos = avaliarSenha(senha.value);
        barra.style.width = `${Math.min(100, pontos * 20)}%`;
        barra.dataset.level = String(pontos);
        const mensagens = ['Muito fraca', 'Muito fraca', 'Fraca', 'Média', 'Boa', 'Forte'];
        texto.textContent = senha.value ? `Força da senha: ${mensagens[pontos]}` : 'Mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.';
    };

    const validarConfirmacao = () => {
        if (!senha || !confirmarSenha) return;
        confirmarSenha.setCustomValidity(confirmarSenha.value && senha.value !== confirmarSenha.value ? 'As senhas não conferem.' : '');
    };

    senha?.addEventListener('input', () => {
        atualizarForcaSenha();
        validarConfirmacao();
    });
    confirmarSenha?.addEventListener('input', validarConfirmacao);
    atualizarForcaSenha();

    $$('[data-secure-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });

    $$('[data-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const ok = confirm('Tem certeza que deseja remover este chamado? Esta ação será registrada na auditoria.');
            if (!ok) event.preventDefault();
        });
    });

    $$('textarea[data-counter]').forEach((textarea) => {
        const target = document.getElementById(textarea.dataset.counter);
        const update = () => {
            if (target) target.textContent = `${textarea.value.length}/${textarea.maxLength} caracteres`;
        };
        textarea.addEventListener('input', update);
        update();
    });

    const searchInput = $('[data-table-search]');
    const statusFilter = $('[data-status-filter]');
    const rows = $$('[data-row]');
    const noResults = $('[data-no-results]');

    const filtrarTabela = () => {
        const termo = (searchInput?.value || '').toLowerCase().trim();
        const status = statusFilter?.value || '';
        let visiveis = 0;
        rows.forEach((row) => {
            const textoLinha = row.textContent.toLowerCase();
            const statusLinha = row.dataset.status || '';
            const mostrar = (!termo || textoLinha.includes(termo)) && (!status || statusLinha === status);
            row.hidden = !mostrar;
            if (mostrar) visiveis++;
        });
        if (noResults) noResults.hidden = visiveis !== 0;
    };

    searchInput?.addEventListener('input', filtrarTabela);
    statusFilter?.addEventListener('change', filtrarTabela);

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    $$('.reveal').forEach((element) => observer.observe(element));
})();
