<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import axiosClient from "../axios";

const router = useRouter();
const orders = ref([]);
const isLoading = ref(true);
const updatingOrderId = ref(null);
const errorMessage = ref("");

const statusLabels = {
  pending: "Niezrealizowane",
  completed: "Zrealizowane",
};

async function loadOrders() {
  isLoading.value = true;
  errorMessage.value = "";
  try {
    const response = await axiosClient.get("/api/admin/orders");
    orders.value = response.data;
  } catch (error) {
    errorMessage.value = error.response?.status === 403
      ? "To konto nie ma uprawnień administratora."
      : "Nie udało się pobrać zamówień.";
  } finally {
    isLoading.value = false;
  }
}

async function updateOrderStatus(order) {
  updatingOrderId.value = order.id;
  errorMessage.value = "";
  try {
    const status = order.status === "completed" ? "pending" : "completed";
    const response = await axiosClient.patch(`/api/admin/orders/${order.id}/status`, { status });
    const index = orders.value.findIndex(item => item.id === order.id);
    if (index !== -1) {
      orders.value[index] = response.data;
    }
  } catch {
    errorMessage.value = "Nie udało się zmienić statusu zamówienia.";
  } finally {
    updatingOrderId.value = null;
  }
}

function formatPrice(value) {
  return `${Number(value).toFixed(2).replace(".", ",")} zł`;
}

onMounted(loadOrders);
</script>

<template>
  <main class="min-h-screen bg-slate-950 text-white px-4 py-8 sm:px-6">
    <div class="mx-auto max-w-5xl">
      <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-sm uppercase tracking-widest text-indigo-300">Laravue Shop</p>
          <h1 class="mt-2 text-3xl font-bold">Panel administratora</h1>
          <p class="mt-1 text-slate-400">Zamówienia: {{ orders.length }}</p>
        </div>
        <div class="flex gap-3">
          <button @click="loadOrders" class="rounded-xl bg-slate-800 px-4 py-2 text-sm text-slate-200 ring-1 ring-white/10 hover:bg-slate-700 cursor-pointer">Odśwież</button>
          <button @click="router.push({ name: 'Home' })" class="rounded-xl bg-indigo-500 px-4 py-2 text-sm font-semibold hover:bg-indigo-400 cursor-pointer">Sklep</button>
        </div>
      </header>

      <p v-if="errorMessage" role="alert" class="mb-5 rounded-xl bg-red-500/10 p-4 text-red-300 ring-1 ring-red-500/30">{{ errorMessage }}</p>
      <p v-if="isLoading" class="py-12 text-center text-slate-400">Wczytywanie zamówień…</p>
      <p v-else-if="orders.length === 0" class="rounded-2xl bg-slate-900 p-10 text-center text-slate-400 ring-1 ring-white/10">Brak zamówień.</p>

      <div v-else class="space-y-5">
        <article v-for="order in orders" :key="order.id" class="rounded-2xl bg-slate-900 p-5 ring-1 ring-white/10 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-4">
            <div>
              <h2 class="text-lg font-bold">{{ order.order_number }}</h2>
              <p class="mt-1 text-sm text-slate-400">{{ new Date(order.created_at).toLocaleString("pl-PL") }}</p>
            </div>
            <span :class="order.status === 'completed' ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30' : 'bg-amber-500/15 text-amber-300 ring-amber-400/30'" class="rounded-full px-3 py-1 text-xs font-semibold ring-1">{{ statusLabels[order.status] || order.status }}</span>
          </div>

          <div class="grid gap-5 py-5 sm:grid-cols-2">
            <div>
              <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Dane klienta</h3>
              <p class="font-medium">{{ order.customer_name }}</p>
              <p class="text-sm text-slate-300">{{ order.customer_email }}</p>
              <p class="text-sm text-slate-300">{{ order.customer_phone }}</p>
              <p class="mt-2 text-sm text-slate-300">{{ order.address_line }}, {{ order.postal_code }} {{ order.city }}</p>
            </div>
            <div>
              <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Produkty</h3>
              <ul class="space-y-2">
                <li v-for="item in order.items" :key="item.id" class="flex justify-between gap-3 text-sm">
                  <span class="text-slate-300">{{ item.product_name }} × {{ item.quantity }}</span>
                  <span class="whitespace-nowrap">{{ formatPrice(item.unit_price * item.quantity) }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4">
            <p class="font-bold">Suma: <span class="text-indigo-300">{{ formatPrice(order.total) }}</span></p>
          </div>

          <section class="mt-5 rounded-xl bg-slate-950/70 p-4 ring-1 ring-white/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Zarządzanie realizacją</h3>
                <p class="mt-1 text-sm text-slate-300">Zmień status tego zamówienia.</p>
              </div>
              <button @click="updateOrderStatus(order)" :disabled="updatingOrderId === order.id" :class="order.status === 'completed' ? 'bg-amber-600 hover:bg-amber-500' : 'bg-emerald-600 hover:bg-emerald-500'" class="rounded-xl px-4 py-2 text-sm font-semibold disabled:opacity-50 cursor-pointer">
                {{ updatingOrderId === order.id ? "Zapisywanie…" : order.status === 'completed' ? "Cofnij realizację" : "Oznacz jako zrealizowane" }}
              </button>
            </div>
          </section>
        </article>
      </div>
    </div>
  </main>
</template>
