import { createApp } from "vue";
import { createPinia } from "pinia";
import "./style.css";
import App from "./App.vue";
import router from "./router";

const pinia = createPinia();

const app = createApp(App);

app.use(pinia); // <-- Pinia musi być zarejestrowana jako pierwsza!
app.use(router); // <-- Następnie router

app.mount("#app");
