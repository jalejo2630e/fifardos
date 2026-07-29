<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({ email: '', password: '', remember: false });

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <div class="fdauth">
        <Head title="Iniciar sesión" />
        <div class="glow" aria-hidden="true"></div>
        <div class="grid-lines" aria-hidden="true"></div>

        <div class="card">
            <Link href="/" class="logo" aria-label="FIFARDOS inicio">FIFAR<span>DOS</span></Link>

            <h1>Iniciá sesión</h1>
            <p class="sub">Entrá a tu cuenta y seguí el torneo.</p>

            <div v-if="status" class="status">{{ status }}</div>

            <form @submit.prevent="submit">
                <label class="lbl" for="email">Correo</label>
                <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                       class="inp" :class="{ err: form.errors.email }" placeholder="vos@correo.com" />
                <p v-if="form.errors.email" class="msg">{{ form.errors.email }}</p>

                <label class="lbl mt" for="password">Contraseña</label>
                <input id="password" type="password" v-model="form.password" required autocomplete="current-password"
                       class="inp" :class="{ err: form.errors.password }" placeholder="••••••••" />
                <p v-if="form.errors.password" class="msg">{{ form.errors.password }}</p>

                <div class="row">
                    <label class="chk">
                        <input type="checkbox" v-model="form.remember" />
                        <span>Recordarme</span>
                    </label>
                    <div class="links">
                        <Link :href="route('password.request')">¿Olvidaste tu contraseña?</Link>
                        <Link :href="route('security-questions.recover.form')" class="dim">Recuperar con preguntas</Link>
                    </div>
                </div>

                <button type="submit" class="btn" :disabled="form.processing">
                    <svg v-if="form.processing" class="spin" viewBox="0 0 24 24" fill="none">
                        <circle class="o25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="o75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ form.processing ? 'Entrando…' : 'Ingresar' }}
                    <span v-if="!form.processing" aria-hidden="true">→</span>
                </button>

                <p class="foot">¿No tenés cuenta? <Link :href="route('register')">Registrate</Link></p>
            </form>
        </div>
    </div>
</template>

<style scoped>
.fdauth {
    --accent: #ff5f00; --accent-hover: #ff7a26; --accent-soft: #ff8a3d;
    --bg: #08080a; --card: #0e0e11; --card2: #131317;
    --tp: #f2f2f0; --ts: #a8a8a3; --tm: #8f8f8b; --tdd: #6d6d69;
    --hair: rgba(255,255,255,.1);
    --f-anton: 'Anton', Impact, sans-serif;
    --f-barlow: 'Barlow Condensed', sans-serif;
    --f-body: 'Chakra Petch', system-ui, sans-serif;
    position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--bg); color: var(--tp); font-family: var(--f-body); padding: 32px 20px; overflow: hidden;
}
.fdauth * { box-sizing: border-box; }
.fdauth ::selection { background: var(--accent); color: var(--bg); }
.glow { position: absolute; top: -160px; left: 50%; transform: translateX(-50%); width: 680px; height: 680px; pointer-events: none;
    background: radial-gradient(circle, rgba(255,95,0,.16), transparent 62%); }
.grid-lines { position: absolute; inset: 0; pointer-events: none;
    background-image: linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
    background-size: 56px 56px; mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 100%); }

.card { position: relative; z-index: 1; width: 100%; max-width: 420px; background: var(--card); border: 1px solid var(--hair); padding: 36px 32px 32px; }
.card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent), transparent); }

.logo { display: inline-block; font-family: var(--f-anton); font-size: 26px; letter-spacing: -.5px; color: var(--tp); text-decoration: none; transform: skewX(-8deg); margin-bottom: 22px; }
.logo span { color: var(--accent); }

h1 { font-family: var(--f-anton); text-transform: uppercase; font-size: 40px; line-height: .95; letter-spacing: -.5px; margin: 0 0 8px; }
.sub { color: var(--tm); font-size: 15px; margin: 0 0 24px; }
.status { background: rgba(182,255,46,.1); border: 1px solid rgba(182,255,46,.3); color: #d4ff8f; font-size: 14px; padding: 10px 14px; margin-bottom: 18px; }

.lbl { display: block; font-family: var(--f-barlow); font-weight: 600; text-transform: uppercase; letter-spacing: .1em; font-size: 13px; color: var(--tm); margin-bottom: 7px; }
.lbl.mt { margin-top: 18px; }
.inp { width: 100%; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 15px; padding: 12px 14px; outline: none; transition: border-color .2s, box-shadow .2s; }
.inp::placeholder { color: var(--tdd); }
.inp:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255,95,0,.15); }
.inp.err { border-color: #ff5a5a; }
.msg { color: #ff7a7a; font-size: 13px; margin: 6px 0 0; }

.row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin: 20px 0; }
.chk { display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--ts); font-size: 14px; }
.chk input { width: 16px; height: 16px; accent-color: var(--accent); }
.links { text-align: right; display: flex; flex-direction: column; gap: 3px; }
.links a { color: var(--accent-soft); text-decoration: none; font-size: 13px; }
.links a:hover { color: #ffb37a; }
.links a.dim { color: var(--tm); font-size: 12px; }

.btn { width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 9px; cursor: pointer;
    background: var(--accent); color: var(--bg); border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 19px; padding: 14px 24px;
    clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px); transition: background-color .2s; }
.btn:hover { background: var(--accent-hover); }
.btn:disabled { opacity: .6; cursor: wait; }
.spin { width: 18px; height: 18px; animation: fdspin 1s linear infinite; }
.o25 { opacity: .25; } .o75 { opacity: .75; }
@keyframes fdspin { to { transform: rotate(360deg); } }

.foot { text-align: center; color: var(--tm); font-size: 14px; margin: 22px 0 0; }
.foot a { color: var(--accent-soft); text-decoration: none; font-weight: 600; }
.foot a:hover { color: #ffb37a; }

@media (max-width: 480px) { h1 { font-size: 32px; } .card { padding: 28px 22px; } .row { flex-direction: column; } .links { text-align: left; } }
@media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
</style>
