<script setup>
import { ref } from "vue";
import axiosClient from "../axios";
import router from "../router";

const data = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});

const errorMessage = ref("");
const fieldErrors = ref({});
const isSubmitting = ref(false);

function validate() {
  const errors = {};
  if (data.value.name.trim().length < 2) errors.name = "Imię i nazwisko musi mieć co najmniej 2 znaki.";
  if (data.value.name.length > 255) errors.name = "Imię i nazwisko może mieć maksymalnie 255 znaków.";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.value.email.trim())) errors.email = "Wpisz poprawny adres e-mail.";
  if (data.value.password.length < 8) errors.password = "Hasło musi mieć co najmniej 8 znaków.";
  if (data.value.password_confirmation !== data.value.password) errors.password_confirmation = "Hasła nie są takie same.";
  fieldErrors.value = errors;
  return Object.keys(errors).length === 0;
}

async function submit() {
  if (isSubmitting.value) return;

  errorMessage.value = "";
  if (!validate()) return;

  isSubmitting.value = true;

  try {
    await axiosClient.get("/sanctum/csrf-cookie");
    await axiosClient.post("/register", data.value);
    await router.push({ name: "Home" });
  } catch (error) {
    const response = error.response;
    if (response?.data?.errors) {
      fieldErrors.value = Object.fromEntries(Object.entries(response.data.errors).map(([key, messages]) => [key, messages[0]]));
    } else {
      errorMessage.value = response?.data?.message || "Nie udało się utworzyć konta. Sprawdź połączenie z serwerem i spróbuj ponownie.";
    }
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen flex-col justify-center bg-slate-950 px-6 py-12 lg:px-8">
    <div class="relative sm:mx-auto sm:w-full sm:max-w-md">
      <!-- Jasna poświata wokół formularza rejestracji -->
      <div 
        class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-indigo-500 via-sky-400 to-purple-500 opacity-35 blur-xl transition duration-500"
        aria-hidden="true">
      </div>

      <!-- Ciemna karta rejestracji -->
      <div class="relative rounded-2xl bg-slate-900/90 px-8 py-10 shadow-2xl ring-1 ring-white/10 backdrop-blur-sm">
        <h2 class="text-center text-2xl/9 font-bold tracking-tight text-white">
          Create new Account
        </h2>

        <div v-if="errorMessage" class="mt-4 rounded-md bg-red-500/20 px-3 py-2 text-center text-sm text-red-300 ring-1 ring-red-500/40">
          {{ errorMessage }}
        </div>

        <form class="mt-8 space-y-4" @submit.prevent="submit">
          <div>
            <label for="name" class="block text-sm/6 font-medium text-slate-300">Full Name</label>
            <div class="mt-2">
              <input
                type="text"
                name="name"
                id="name"
                v-model="data.name"
                maxlength="255"
                :aria-invalid="Boolean(fieldErrors.name)"
                @input="fieldErrors.name = ''"
                required
                placeholder="Jan Kowalski"
                class="block w-full rounded-md bg-slate-800/80 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-slate-700 placeholder:text-slate-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-400 sm:text-sm/6"
              />
            </div>
            <p v-if="fieldErrors.name" class="mt-1 text-sm text-red-400">{{ fieldErrors.name }}</p>
          </div>

          <div>
            <label for="email" class="block text-sm/6 font-medium text-slate-300">Email address</label>
            <div class="mt-2">
              <input
                type="email"
                name="email"
                id="email"
                v-model="data.email"
                :aria-invalid="Boolean(fieldErrors.email)"
                @input="fieldErrors.email = ''"
                autocomplete="email"
                required
                placeholder="you@example.com"
                class="block w-full rounded-md bg-slate-800/80 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-slate-700 placeholder:text-slate-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-400 sm:text-sm/6"
              />
            </div>
            <p v-if="fieldErrors.email" class="mt-1 text-sm text-red-400">{{ fieldErrors.email }}</p>
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label for="password" class="block text-sm/6 font-medium text-slate-300">Password</label>
            </div>
            <div class="mt-2">
              <input
                type="password"
                name="password"
                v-model="data.password"
                minlength="8"
                :aria-invalid="Boolean(fieldErrors.password)"
                @input="fieldErrors.password = ''; fieldErrors.password_confirmation = ''"
                id="password"
                required
                placeholder="••••••••"
                class="block w-full rounded-md bg-slate-800/80 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-slate-700 placeholder:text-slate-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-400 sm:text-sm/6"
              />
            </div>
            <p v-if="fieldErrors.password" class="mt-1 text-sm text-red-400">{{ fieldErrors.password }}</p>
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label for="passwordConfirmation" class="block text-sm/6 font-medium text-slate-300">Repeat Password</label>
            </div>
            <div class="mt-2">
              <input
                type="password"
                name="password_confirmation"
                id="passwordConfirmation"
                v-model="data.password_confirmation"
                :aria-invalid="Boolean(fieldErrors.password_confirmation)"
                @input="fieldErrors.password_confirmation = ''"
                required
                placeholder="••••••••"
                class="block w-full rounded-md bg-slate-800/80 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-slate-700 placeholder:text-slate-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-400 sm:text-sm/6"
              />
            </div>
            <p v-if="fieldErrors.password_confirmation" class="mt-1 text-sm text-red-400">{{ fieldErrors.password_confirmation }}</p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400 transition-colors disabled:opacity-50"
            >
              {{ isSubmitting ? "Tworzenie konta..." : "Create an Account" }}
            </button>
          </div>
        </form>

        <p class="mt-8 text-center text-sm/6 text-slate-400">
          Already have an account?
          <RouterLink
            :to="{ name: 'login' }"
            class="font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
          >
            Login from here
          </RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
