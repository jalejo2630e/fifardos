// Identidad ligera del navegador para el módulo Familia (sin cuenta).
// Un token persistente identifica a "esta familia" en las salas.

const TOKEN_KEY = 'familia:token';
const NAME_KEY = 'familia:name';

export function getToken() {
    let t = localStorage.getItem(TOKEN_KEY);
    if (!t) {
        t = (crypto.randomUUID && crypto.randomUUID())
            || (Date.now().toString(36) + Math.random().toString(36).slice(2, 10));
        localStorage.setItem(TOKEN_KEY, t);
    }
    return t;
}

export function getName() {
    return localStorage.getItem(NAME_KEY) || '';
}

export function setName(name) {
    localStorage.setItem(NAME_KEY, name);
}
