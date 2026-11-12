<script setup>
import { onMounted, ref, computed } from "vue";
import axiosClient from "../axios";
import { useRouter } from "vue-router";
import { useUserStore } from "../store/user";

const router = useRouter();
const userStore = useUserStore();
const user = ref(null);
const errorMessage = ref("");
const logoutError = ref("");
const isLoggingOut = ref(false);
const selectedCategory = ref("Wszystkie");
const selectedProduct = ref(null);
const isCheckoutOpen = ref(false);
const isSubmittingOrder = ref(false);
const orderError = ref("");
const orderErrors = ref({});
const orderSuccess = ref("");
const orderForm = ref({
  customer_name: "",
  customer_email: "",
  customer_phone: "",
  address_line: "",
  postal_code: "",
  city: "",
});

// Lista produktów (docelowo możesz pobierać z bazy przez API)
const products = ref([
  { id: 1, name: "Kolekcja Premium", price: 299, category: "Odzież", badge: "Nowość", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Miękki materiał i staranne wykończenie sprawdzą się na co dzień." },
  { id: 2, name: "Zestaw Minimalistyczny", price: 149, category: "Akcesoria", badge: "-20%", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Prosty zestaw, który pasuje do wielu stylizacji." },
  { id: 3, name: "Edycja Limitowana", price: 499, category: "Obuwie", badge: "Hit", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Wygodny fason i ponadczasowy wygląd na różne okazje." },
  { id: 4, name: "Klasyczny Model", price: 199, category: "Odzież", badge: null, description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Klasyczny krój i komfort noszenia przez cały dzień." },
  { id: 5, name: "Akcesorium Smart", price: 349, category: "Elektronika", badge: "Bestseller", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praktyczny dodatek, który ułatwia codzienne zadania." },
  { id: 6, name: "Zestaw Prezentowy", price: 229, category: "Zestawy", badge: null, description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Gotowy pomysł na prezent dla bliskiej osoby." },
]);

const categories = computed(() => ["Wszystkie", ...new Set(products.value.map(product => product.category))]);
const filteredProducts = computed(() => selectedCategory.value === "Wszystkie"
  ? products.value
  : products.value.filter(product => product.category === selectedCategory.value));


async function logout() {
  if (isLoggingOut.value) return;

  isLoggingOut.value = true;
  logoutError.value = "";

  try {
    await axiosClient.post("/api/logout");

    localStorage.removeItem("token");

    userStore.clearUser();
    user.value = null;

    await router.push({ name: "login" });

  } catch (error) {
    console.error("Błąd wylogowania:", error);

    logoutError.value =
      "Nie udało się wylogować. Spróbuj ponownie.";

  } finally {
    isLoggingOut.value = false;
  }
}


function openCheckout() {
  orderError.value = "";
  orderErrors.value = {};
  orderSuccess.value = "";
  if (user.value) {
    orderForm.value.customer_name = user.value.name || orderForm.value.customer_name;
    orderForm.value.customer_email = user.value.email || orderForm.value.customer_email;
  }
  isCartOpen.value = false;
  isCheckoutOpen.value = true;
}

async function submitOrder() {
  orderError.value = "";
  const errors = {};
  if (orderForm.value.customer_name.trim().length < 2) errors.customer_name = "Podaj imię i nazwisko (co najmniej 2 znaki).";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(orderForm.value.customer_email.trim())) errors.customer_email = "Wpisz poprawny adres e-mail.";
  const phoneDigits = orderForm.value.customer_phone.replace(/\D/g, "");
  if (phoneDigits.length < 7 || phoneDigits.length > 15) errors.customer_phone = "Numer telefonu powinien zawierać od 7 do 15 cyfr.";
  if (orderForm.value.address_line.trim().length < 5) errors.address_line = "Podaj ulicę i numer budynku.";
  if (!/^\d{2}-\d{3}$/.test(orderForm.value.postal_code.trim())) errors.postal_code = "Wpisz kod pocztowy w formacie 00-000.";
  if (orderForm.value.city.trim().length < 2) errors.city = "Podaj nazwę miasta.";
  orderErrors.value = errors;
  if (Object.keys(errors).length > 0) return;

  isSubmittingOrder.value = true;
  try {
    const response = await axiosClient.post("/api/orders", {
      ...orderForm.value,
      items: cart.value.map(item => ({ id: item.id, quantity: item.quantity })),
    });
    orderSuccess.value = `Dziękujemy za zamówienie! Numer zamówienia: ${response.data.order_number}`;
    cart.value = [];
    persistCart();
    if (user.value) {
      await saveCartToDatabase();
    }
  } catch (error) {
    const serverErrors = error.response?.data?.errors;
    if (serverErrors) {
      orderErrors.value = Object.fromEntries(Object.entries(serverErrors)
        .filter(([field]) => !field.startsWith("items"))
        .map(([field, messages]) => [field, messages[0]]));
      orderError.value = Object.values(serverErrors).flat()[0] || "Popraw zaznaczone pola.";
    } else {
      orderError.value = error.response?.data?.message || "Nie udało się złożyć zamówienia. Sprawdź połączenie i spróbuj ponownie.";
    }
  } finally {
    isSubmittingOrder.value = false;
  }
}

// --- MECHANIKA KOSZYKA ---
const cart = ref([]);
const isCartOpen = ref(false);
const CART_STORAGE_KEY = "laravue-shop-cart";

function parsePrice(price) {
  if (typeof price === 'number') return price;
  return parseFloat(price.replace(/[^0-9,.]/g, '').replace(',', '.')) || 0;
}

// Pobieranie koszyka z bazy danych
async function fetchCart() {
  try {
    const response = await axiosClient.get("/api/cart");
    if (Array.isArray(response.data?.items)) {
      if (response.data.items.length > 0) {
        cart.value = response.data.items.map(item => {
          const product = products.value.find(p => p.id === item.product_id);
          return {
            id: item.product_id,
            name: product ? product.name : `Produkt #${item.product_id}`,
            numericPrice: product ? parsePrice(product.price) : 0,
            quantity: item.quantity
          };
        });
        persistCart();
      } else if (cart.value.length > 0) {
        await saveCartToDatabase();
      } else {
        persistCart();
      }
    }
  } catch (error) {
    // Koszyk lokalny pozostaje dostępny, gdy użytkownik jest wylogowany lub API nie działa.
  }
}

function persistCart() {
  localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart.value));
}

function restoreCart() {
  try {
    const savedCart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "[]");
    if (Array.isArray(savedCart)) {
      cart.value = savedCart.filter(item =>
        Number.isInteger(item.id) && Number.isInteger(item.quantity) && item.quantity > 0
      );
    }
  } catch {
    localStorage.removeItem(CART_STORAGE_KEY);
  }
}

// Zapisywanie koszyka w bazie danych
async function saveCartToDatabase() {
  persistCart();
  try {
    await axiosClient.post("/api/cart/sync", {
      items: cart.value.map(item => ({
        id: item.id,
        quantity: item.quantity
      }))
    });
  } catch (error) {
    console.error("Błąd podczas zapisywania koszyka", error);
  }
}

// Dodawanie do koszyka
function addToCart(product) {
  const existingItem = cart.value.find(item => item.id === product.id);
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.value.push({
      id: product.id,
      name: product.name,
      numericPrice: parsePrice(product.price),
      quantity: 1
    });
  }
  saveCartToDatabase();
}

// Zwiększanie ilości w koszyku
function increaseQuantity(item) {
  item.quantity += 1;
  saveCartToDatabase();
}

// Zmniejszanie / usuwanie z koszyka
function removeFromCart(productId) {
  const index = cart.value.findIndex(item => item.id === productId);
  if (index !== -1) {
    if (cart.value[index].quantity > 1) {
      cart.value[index].quantity -= 1;
    } else {
      cart.value.splice(index, 1);
    }
    saveCartToDatabase();
  }
}

function removeItemFromCart(productId) {
  cart.value = cart.value.filter(item => item.id !== productId);
  saveCartToDatabase();
}

// Liczniki i suma
const cartCount = computed(() => {
  return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const cartTotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.numericPrice * item.quantity), 0);
});

// Ładowanie użytkownika i koszyka przy starcie
onMounted(async () => {
  restoreCart();

  try {
    const userResponse = await axiosClient.get("/api/user");
    user.value = userResponse.data;
  } catch {
    errorMessage.value = "Nie udało się pobrać danych użytkownika.";
  }

  await fetchCart();
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col relative overflow-x-hidden">
    <!-- Navbar -->
    <header class="border-b border-white/10 bg-slate-900/50 backdrop-blur-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="text-xl font-bold tracking-wider bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
          LARAVUE SHOP
        </div>
        
        <div class="flex items-center gap-6">
          <div class="text-sm text-slate-300 hidden sm:block">
            <span v-if="user">Witaj, <strong class="text-white">{{ user.name || user.email }}</strong>!</span>
            <span v-else-if="errorMessage" class="text-red-400 text-xs">{{ errorMessage }}</span>
            <span v-else class="text-slate-500 text-xs animate-pulse">Ładowanie profilu...</span>
          </div>

          <button
            v-if="user?.is_admin"
            @click="router.push({ name: 'Admin' })"
            class="px-3 py-2 rounded-xl text-sm text-indigo-300 ring-1 ring-indigo-400/30 hover:bg-indigo-500/10 transition-all cursor-pointer"
          >
            Panel admina
          </button>

          <button
            v-if="user"
            @click="logout"
            :disabled="isLoggingOut"
            class="px-3 py-2 rounded-xl text-sm text-slate-300 ring-1 ring-white/10 hover:bg-slate-800 hover:text-white transition-all disabled:opacity-50 cursor-pointer"
          >
            {{ isLoggingOut ? "Wylogowywanie..." : "Wyloguj" }}
          </button>

          <button 
            @click="isCartOpen = true"
            class="relative px-4 py-2 rounded-xl bg-slate-800/80 ring-1 ring-white/10 text-slate-200 hover:bg-slate-800 hover:text-white transition-all flex items-center gap-2 cursor-pointer"
          >
            🛒 Koszyk
            <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 bg-indigo-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-md">
              {{ cartCount }}
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative max-w-7xl mx-auto px-6 pt-16 pb-10 text-center">
      <div class="absolute inset-0 flex items-center justify-center -z-10">
        <div class="w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
      </div>
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">
        Odkryj naszą ofertę
      </h1>
      <p class="text-slate-400 max-w-xl mx-auto text-base">
        Wybierz spośród najlepszych produktów w nowoczesnym wydaniu.
      </p>
      <p v-if="logoutError" class="mt-4 text-sm text-red-400">{{ logoutError }}</p>
    </section>

    <!-- Siatka produktów -->
    <main class="flex-1 max-w-7xl mx-auto px-6 pb-20 w-full">
      <nav aria-label="Kategorie produktów" class="flex flex-wrap justify-center gap-3 mb-8">
        <button
          v-for="category in categories"
          :key="category"
          @click="selectedCategory = category"
          :aria-pressed="selectedCategory === category"
          :class="selectedCategory === category
            ? 'bg-indigo-500 text-white ring-indigo-400'
            : 'bg-slate-900 text-slate-300 ring-white/10 hover:bg-slate-800 hover:text-white'"
          class="px-4 py-2 rounded-full text-sm font-semibold ring-1 transition-colors cursor-pointer"
        >
          {{ category }}
        </button>
      </nav>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="product in filteredProducts" 
          :key="product.id"
          class="group relative rounded-2xl bg-slate-900/80 p-4 ring-1 ring-white/10 shadow-xl hover:ring-indigo-500/50 transition-all duration-300 flex flex-col justify-between"
        >
          <!-- Poświata kwadratu -->
          <div class="absolute -inset-0.5 rounded-2xl bg-gradient-to-r from-indigo-500 via-sky-400 to-purple-500 opacity-0 group-hover:opacity-35 blur transition duration-500 -z-10"></div>

          <div>
            <div class="flex items-center justify-between mb-4">
              <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">{{ product.category }}</span>
              <span v-if="product.badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-500/30">
                {{ product.badge }}
              </span>
            </div>

            <button @click="selectedProduct = product" class="aspect-square w-full rounded-xl bg-slate-800/80 ring-1 ring-white/5 flex items-center justify-center mb-4 overflow-hidden relative group-hover:scale-[1.02] transition-transform duration-300 cursor-pointer" :aria-label="`Zobacz opis produktu: ${product.name}`">
              <span class="text-slate-500 text-sm font-medium">Zdjęcie produktu</span>
            </button>

            <button @click="selectedProduct = product" class="text-left text-lg font-semibold text-white mb-1 group-hover:text-indigo-300 transition-colors cursor-pointer">
              {{ product.name }}
            </button>
            <p class="text-indigo-400 font-bold text-base mb-4">
              {{ product.price }} zł
            </p>
          </div>

          <button 
            @click="addToCart(product)"
            class="w-full py-2.5 px-4 rounded-xl bg-slate-800 text-sm font-semibold text-slate-200 ring-1 ring-white/10 hover:bg-indigo-600 hover:text-white hover:ring-indigo-500 transition-all duration-200 shadow-sm cursor-pointer"
          >
            Dodaj do koszyka
          </button>
        </div>
      </div>
    </main>

    <div v-if="selectedProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <button class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-default" aria-label="Zamknij opis produktu" @click="selectedProduct = null"></button>
      <section role="dialog" aria-modal="true" :aria-label="`Opis produktu ${selectedProduct.name}`" class="relative z-10 w-full max-w-lg rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <p class="text-xs uppercase tracking-wider text-indigo-300 mb-2">{{ selectedProduct.category }}</p>
            <h2 class="text-2xl font-bold">{{ selectedProduct.name }}</h2>
          </div>
          <button @click="selectedProduct = null" class="text-slate-400 hover:text-white text-lg font-bold px-2 cursor-pointer" aria-label="Zamknij">✕</button>
        </div>
        <div class="aspect-video rounded-xl bg-slate-800 ring-1 ring-white/5 flex items-center justify-center mb-5">
          <span class="text-slate-500 text-sm">Zdjęcie produktu</span>
        </div>
        <p class="text-slate-300 leading-relaxed mb-5">{{ selectedProduct.description }}</p>
        <div class="flex items-center justify-between gap-4">
          <span class="text-xl font-bold text-indigo-400">{{ selectedProduct.price }} zł</span>
          <button @click="addToCart(selectedProduct); selectedProduct = null" class="px-5 py-3 rounded-xl bg-indigo-500 text-white font-semibold hover:bg-indigo-400 transition-colors cursor-pointer">Dodaj do koszyka</button>
        </div>
      </section>
    </div>

    <!-- Modal Koszyka (Drawer) -->
    <div v-if="isCartOpen" class="fixed inset-0 z-50 flex justify-end">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="isCartOpen = false"></div>

      <div class="relative w-full max-w-md bg-slate-900 border-l border-white/10 p-6 flex flex-col shadow-2xl z-10 h-full">
        <div class="flex items-center justify-between pb-4 border-b border-white/10">
          <h2 class="text-xl font-bold text-white">Twój Koszyk ({{ cartCount }})</h2>
          <button @click="isCartOpen = false" class="text-slate-400 hover:text-white text-lg font-bold px-2 py-1 cursor-pointer">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-4">
          <div v-if="cart.length === 0" class="text-center text-slate-500 py-12">
            Twoja torba jest pusta.
          </div>

          <div v-for="item in cart" :key="item.id" class="flex items-center justify-between bg-slate-800/50 p-3 rounded-xl ring-1 ring-white/5">
            <div>
              <h4 class="text-sm font-medium text-white">{{ item.name }}</h4>
              <p class="text-xs text-indigo-400">{{ item.numericPrice }} zł x {{ item.quantity }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="removeItemFromCart(item.id)" :aria-label="`Usuń ${item.name} z koszyka`" title="Usuń z koszyka" class="w-7 h-7 rounded-lg bg-slate-700 text-slate-300 hover:bg-red-500/20 hover:text-red-400 hover:ring-1 hover:ring-red-500/40 transition-all flex items-center justify-center text-sm font-bold cursor-pointer">×</button>
              <button @click="removeFromCart(item.id)" class="w-7 h-7 rounded-lg bg-slate-700 text-slate-300 hover:bg-red-500/20 hover:text-red-400 hover:ring-1 hover:ring-red-500/40 transition-all flex items-center justify-center text-sm font-bold cursor-pointer">-</button>
              <span class="text-sm font-semibold w-5 text-center">{{ item.quantity }}</span>
              <button @click="increaseQuantity(item)" class="w-7 h-7 rounded-lg bg-slate-700 text-slate-300 hover:bg-indigo-500/20 hover:text-indigo-400 hover:ring-1 hover:ring-indigo-500/40 transition-all flex items-center justify-center text-sm font-bold cursor-pointer">+</button>
            </div>
          </div>
        </div>

        <div v-if="cart.length > 0" class="pt-4 border-t border-white/10">
          <div class="flex justify-between text-base font-semibold text-white mb-4">
            <span>Razem do zapłaty:</span>
            <span class="text-indigo-400 font-bold">{{ cartTotal }} zł</span>
          </div>
          <button 
            @click="openCheckout"
            class="w-full py-3 rounded-xl bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:bg-indigo-400 transition-colors cursor-pointer"
          >
            Przejdź do zamówienia
          </button>
        </div>
      </div>
    </div>

    <div v-if="isCheckoutOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <button class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" aria-label="Zamknij formularz zamówienia" @click="isCheckoutOpen = false"></button>
      <section role="dialog" aria-modal="true" aria-labelledby="checkout-title" class="relative z-10 w-full max-w-xl rounded-2xl bg-slate-900 border border-white/10 p-6 shadow-2xl my-8">
        <div class="flex items-center justify-between gap-4 mb-6">
          <h2 id="checkout-title" class="text-2xl font-bold">Dane do zamówienia</h2>
          <button @click="isCheckoutOpen = false" class="text-slate-400 hover:text-white text-lg font-bold px-2 cursor-pointer" aria-label="Zamknij">✕</button>
        </div>

        <div v-if="orderSuccess" class="rounded-xl bg-emerald-500/10 ring-1 ring-emerald-400/30 p-5">
          <p class="text-emerald-300 font-semibold">{{ orderSuccess }}</p>
          <p class="mt-2 text-sm text-slate-300">Zamówienie zostało zapisane i oczekuje na realizację.</p>
          <button @click="isCheckoutOpen = false" class="mt-5 px-5 py-2.5 rounded-xl bg-indigo-500 text-white font-semibold hover:bg-indigo-400 cursor-pointer">Gotowe</button>
        </div>

        <form v-else @submit.prevent="submitOrder" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="text-sm text-slate-300">Imię i nazwisko
              <input v-model.trim="orderForm.customer_name" required maxlength="150" autocomplete="name" :aria-invalid="Boolean(orderErrors.customer_name)" @input="orderErrors.customer_name = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.customer_name" class="mt-1 block text-xs text-red-400">{{ orderErrors.customer_name }}</span>
            </label>
            <label class="text-sm text-slate-300">E-mail
              <input v-model.trim="orderForm.customer_email" required type="email" maxlength="255" autocomplete="email" :aria-invalid="Boolean(orderErrors.customer_email)" @input="orderErrors.customer_email = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.customer_email" class="mt-1 block text-xs text-red-400">{{ orderErrors.customer_email }}</span>
            </label>
            <label class="text-sm text-slate-300">Telefon
              <input v-model.trim="orderForm.customer_phone" required type="tel" maxlength="30" autocomplete="tel" :aria-invalid="Boolean(orderErrors.customer_phone)" @input="orderErrors.customer_phone = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.customer_phone" class="mt-1 block text-xs text-red-400">{{ orderErrors.customer_phone }}</span>
            </label>
            <label class="text-sm text-slate-300">Ulica i numer
              <input v-model.trim="orderForm.address_line" required maxlength="255" autocomplete="street-address" :aria-invalid="Boolean(orderErrors.address_line)" @input="orderErrors.address_line = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.address_line" class="mt-1 block text-xs text-red-400">{{ orderErrors.address_line }}</span>
            </label>
            <label class="text-sm text-slate-300">Kod pocztowy
              <input v-model.trim="orderForm.postal_code" required maxlength="6" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000" autocomplete="postal-code" inputmode="numeric" :aria-invalid="Boolean(orderErrors.postal_code)" @input="orderErrors.postal_code = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.postal_code" class="mt-1 block text-xs text-red-400">{{ orderErrors.postal_code }}</span>
            </label>
            <label class="text-sm text-slate-300">Miasto
              <input v-model.trim="orderForm.city" required maxlength="100" autocomplete="address-level2" :aria-invalid="Boolean(orderErrors.city)" @input="orderErrors.city = ''" class="mt-1 w-full rounded-lg bg-slate-800 border border-white/10 px-3 py-2.5 text-white outline-none focus:border-indigo-400" />
              <span v-if="orderErrors.city" class="mt-1 block text-xs text-red-400">{{ orderErrors.city }}</span>
            </label>
          </div>

          <div class="flex justify-between border-t border-white/10 pt-4 text-base font-semibold">
            <span>Do zapłaty</span>
            <span class="text-indigo-400">{{ cartTotal }} zł</span>
          </div>
          <p v-if="orderError" role="alert" class="text-sm text-red-400">{{ orderError }}</p>
          <button type="submit" :disabled="isSubmittingOrder || cart.length === 0" class="w-full py-3 rounded-xl bg-indigo-500 text-white font-semibold hover:bg-indigo-400 transition-colors disabled:opacity-50 cursor-pointer">
            {{ isSubmittingOrder ? "Zapisywanie zamówienia..." : "Złóż zamówienie" }}
          </button>
        </form>
      </section>
    </div>

    <!-- Stopka -->
    <footer class="border-t border-white/10 py-8 text-center text-sm text-slate-500">
      <p>&copy; 2026 Laravue Shop. Wszystkie prawa zastrzeżone.</p>
    </footer>
  </div>
</template>
