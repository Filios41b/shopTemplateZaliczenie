import { createRouter, createWebHashHistory } from "vue-router";

// Importy z uwzględnieniem folderów components i pages
import Login from "./pages/Login.vue";
import SignUp from "./pages/SignUp.vue";
import Home from "./pages/Home.vue";
import Admin from "./pages/Admin.vue";

const routes = [
  {
    path: "/",
    name: "Home",
    component: Home,
  },
  {
    path: "/login",
    name: "login",
    component: Login,
  },
  {
    path: "/register",
    name: "register",
    component: SignUp,
  },
  {
    path: "/admin",
    name: "Admin",
    component: Admin,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
