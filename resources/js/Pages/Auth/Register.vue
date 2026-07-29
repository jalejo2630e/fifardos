<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const AVATARS = ['⚽', '🥅', '🏆', '⭐', '🔥', '💥', '👑', '🦁', '🐺', '🦅', '🐉', '⚡', '💀', '🎯', '🚀', '👹'];

const props = defineProps({
    captcha: { type: Object, default: () => ({ a: 0, b: 0 }) },
});

const form = useForm({
    name: '', email: '', password: '', password_confirmation: '', avatar: '', captcha: '', website: '',
});

const avatarFile = ref(null);
const avatarPreview = ref('');
const showEmojiPicker = ref(false);

function selectEmoji(emoji) {
    form.avatar = emoji; avatarFile.value = null; avatarPreview.value = emoji; showEmojiPicker.value = false;
}
function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    avatarFile.value = file; form.avatar = '';
    const reader = new FileReader();
    reader.onload = (ev) => { avatarPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}
const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        forceFormData: !!avatarFile.value,
    });
};
</script>

<template>
    <div class="fdauth">
        <Head title="Crear cuenta" />
        <div class="glow" aria-hidden="true"></div>
        <div class="grid-lines" aria-hidden="true"></div>

        <div class="card">
            <Link href="/" class="logo" aria-label="FIFARDOS inicio">FIFAR<span>DOS</span></Link>

            <h1>Armá tu cuenta</h1>
            <p class="sub">Gratis, sin instalar nada. En un minuto estás jugando.</p>

            <form @submit.prevent="submit">
                <label class="lbl" for="name">Nombre</label>
                <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name"
                       class="inp" :class="{ err: form.errors.name }" placeholder="Tu nombre" />
                <p v-if="form.errors.name" class="msg">{{ form.errors.name }}</p>

                <label class="lbl mt" for="email">Correo</label>
                <input id="email" type="email" v-model="form.email" required autocomplete="username"
                       class="inp" :class="{ err: form.errors.email }" placeholder="vos@correo.com" />
                <p v-if="form.errors.email" class="msg">{{ form.errors.email }}</p>

                <div class="two">
                    <div>
                        <label class="lbl mt" for="password">Contraseña</label>
                        <input id="password" type="password" v-model="form.password" required autocomplete="new-password"
                               class="inp" :class="{ err: form.errors.password }" placeholder="••••••••" />
                    </div>
                    <div>
                        <label class="lbl mt" for="password_confirmation">Confirmar</label>
                        <input id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password"
                               class="inp" placeholder="••••••••" />
                    </div>
                </div>
                <p v-if="form.errors.password" class="msg">{{ form.errors.password }}</p>

                <label class="lbl mt">Avatar <span class="opt">· opcional</span></label>
                <div class="avatar-row">
                    <div class="avatar-prev">
                        <img v-if="avatarPreview && avatarPreview.startsWith('data:')" :src="avatarPreview" />
                        <span v-else>{{ avatarPreview || '?' }}</span>
                    </div>
                    <button type="button" class="mini" @click="showEmojiPicker = !showEmojiPicker">😀 Emoji</button>
                    <label class="mini">📷 Foto<input type="file" accept="image/*" class="hidden" @change="onFileChange" /></label>
                </div>
                <div v-if="showEmojiPicker" class="emoji-grid">
                    <button type="button" v-for="e in AVATARS" :key="e" @click="selectEmoji(e)"
                            class="emoji" :class="{ sel: form.avatar === e }">{{ e }}</button>
                </div>
                <p v-if="form.errors.avatar" class="msg">{{ form.errors.avatar }}</p>

                <!-- Honeypot anti-bots (oculto para humanos) -->
                <input type="text" v-model="form.website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true" />

                <label class="lbl mt" for="captcha">Verificación · ¿Cuánto es {{ captcha.a }} + {{ captcha.b }}?</label>
                <input id="captcha" type="text" inputmode="numeric" v-model="form.captcha" required autocomplete="off"
                       class="inp" :class="{ err: form.errors.captcha }" placeholder="Resultado" />
                <p v-if="form.errors.captcha" class="msg">{{ form.errors.captcha }}</p>

                <button type="submit" class="btn" :disabled="form.processing">
                    <svg v-if="form.processing" class="spin" viewBox="0 0 24 24" fill="none">
                        <circle class="o25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="o75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ form.processing ? 'Creando…' : 'Crear cuenta' }}
                    <span v-if="!form.processing" aria-hidden="true">→</span>
                </button>

                <p class="foot">¿Ya tenés cuenta? <Link :href="route('login')">Iniciá sesión</Link></p>
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

.card { position: relative; z-index: 1; width: 100%; max-width: 460px; background: var(--card); border: 1px solid var(--hair); padding: 34px 32px 30px; }
.card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent), transparent); }

.logo { display: inline-block; font-family: var(--f-anton); font-size: 26px; letter-spacing: -.5px; color: var(--tp); text-decoration: none; transform: skewX(-8deg); margin-bottom: 20px; }
.logo span { color: var(--accent); }

h1 { font-family: var(--f-anton); text-transform: uppercase; font-size: 38px; line-height: .95; letter-spacing: -.5px; margin: 0 0 8px; }
.sub { color: var(--tm); font-size: 15px; margin: 0 0 22px; }

.lbl { display: block; font-family: var(--f-barlow); font-weight: 600; text-transform: uppercase; letter-spacing: .1em; font-size: 13px; color: var(--tm); margin-bottom: 7px; }
.lbl.mt { margin-top: 16px; }
.lbl .opt { color: var(--tdd); font-weight: 400; text-transform: none; letter-spacing: 0; }
.inp { width: 100%; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 15px; padding: 12px 14px; outline: none; transition: border-color .2s, box-shadow .2s; }
.inp::placeholder { color: var(--tdd); }
.inp:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255,95,0,.15); }
.inp.err { border-color: #ff5a5a; }
.msg { color: #ff7a7a; font-size: 13px; margin: 6px 0 0; }
.hp { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.avatar-row { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
.avatar-prev { width: 46px; height: 46px; background: var(--card2); border: 1px solid var(--hair); display: flex; align-items: center; justify-content: center; font-size: 22px; overflow: hidden; flex-shrink: 0; }
.avatar-prev img { width: 100%; height: 100%; object-fit: cover; }
.mini { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; background: var(--card2); border: 1px solid var(--hair); color: var(--ts); font-family: var(--f-body); font-size: 13px; padding: 9px 12px; }
.mini:hover { border-color: var(--accent); }
.hidden { display: none; }
.emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 6px; margin-top: 10px; padding: 10px; background: var(--card2); border: 1px solid var(--hair); }
.emoji { width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; background: transparent; border: 1px solid transparent; font-size: 18px; }
.emoji:hover { background: rgba(255,255,255,.06); }
.emoji.sel { background: rgba(255,95,0,.15); border-color: var(--accent); }

.btn { width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 9px; cursor: pointer; margin-top: 22px;
    background: var(--accent); color: var(--bg); border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 19px; padding: 14px 24px;
    clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px); transition: background-color .2s; }
.btn:hover { background: var(--accent-hover); }
.btn:disabled { opacity: .6; cursor: wait; }
.spin { width: 18px; height: 18px; animation: fdspin 1s linear infinite; }
.o25 { opacity: .25; } .o75 { opacity: .75; }
@keyframes fdspin { to { transform: rotate(360deg); } }

.foot { text-align: center; color: var(--tm); font-size: 14px; margin: 20px 0 0; }
.foot a { color: var(--accent-soft); text-decoration: none; font-weight: 600; }
.foot a:hover { color: #ffb37a; }

@media (max-width: 480px) { h1 { font-size: 30px; } .card { padding: 26px 20px; } .two { grid-template-columns: 1fr; gap: 0; } }
@media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
</style>
