import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: import.meta.env.BASE_URL,
  routes: [
    {
      path: "/",
      name: "patients-list",
      component: () => import("../views/PatientsListView.vue"),
    },
    {
      path: "/pacientes/novo",
      name: "patient-create",
      component: () => import("../views/PatientFormView.vue"),
    },
    {
      path: "/pacientes/excluidos",
      name: "patients-trashed",
      component: () => import("../views/DeletedPatientsView.vue"),
    },
    {
      path: "/pacientes/:id",
      name: "patient-edit",
      component: () => import("../views/PatientFormView.vue"),
      props: true,
    },
  ],
});

export default router;
