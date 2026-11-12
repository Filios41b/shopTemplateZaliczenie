import { defineStore } from "pinia";
import axiosClient from "../axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null,
  }),

  actions: {
    async fetchUser() {
      try {
        const response = await axiosClient.get("/api/user");
        this.user = response.data;
        return response.data;
      } catch (error) {
        this.user = null;
        throw error;
      }
    },

    clearUser() {
      this.user = null;
    },
  },
});
