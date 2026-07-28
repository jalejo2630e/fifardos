const COLORS = ['#F97316', '#FFD700', '#FFFFFF', '#ea580c', '#FFB800', '#FF8C00'];
const SHAPES = ['circle', 'square', 'strip'];

function randomBetween(min, max) {
    return Math.random() * (max - min) + min;
}

export function triggerConfetti(count = 40) {
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed; inset: 0; z-index: 9999;
        pointer-events: none; overflow: hidden;
    `;

    const style = document.createElement('style');
    style.textContent = `
        @keyframes confetti-fall {
            0% { transform: translateY(-10vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }
        @keyframes confetti-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(30px); }
            75% { transform: translateX(-30px); }
        }
        .confetti-piece {
            position: absolute;
            top: -10vh;
            animation: confetti-fall var(--fall-duration) ease-in forwards;
        }
        .confetti-piece.circle {
            width: var(--size); height: var(--size);
            border-radius: 50%;
            background: var(--color);
        }
        .confetti-piece.square {
            width: var(--size); height: var(--size);
            border-radius: 2px;
            background: var(--color);
        }
        .confetti-piece.strip {
            width: var(--size); height: calc(var(--size) * 0.35);
            border-radius: 1px;
            background: var(--color);
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(container);

    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        const shape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
        const size = randomBetween(6, 12);
        piece.className = `confetti-piece ${shape}`;
        piece.style.setProperty('--color', COLORS[Math.floor(Math.random() * COLORS.length)]);
        piece.style.setProperty('--size', size + 'px');
        piece.style.setProperty('--fall-duration', randomBetween(2, 3.5) + 's');
        piece.style.left = randomBetween(0, 100) + '%';
        piece.style.animationDelay = randomBetween(0, 1.5) + 's';
        container.appendChild(piece);
    }

    setTimeout(() => {
        container.remove();
        style.remove();
    }, 4500);
}
