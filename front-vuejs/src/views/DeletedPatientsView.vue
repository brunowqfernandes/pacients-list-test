<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

interface Patient {
  id: number;
  name: string;
  cpf: string;
  cellphone: string;
  birth_date: string;
}

const patients = ref<Patient[]>([]);
const search = ref("");
const isLoading = ref(false);
const errorMessage = ref("");
const currentPage = ref(1);

const itemsPerPage = 10;
const restoringPatientId = ref<number | null>(null);
const successMessage = ref("");

async function restorePatient(patientId: number) {
  if (restoringPatientId.value !== null) return;

  restoringPatientId.value = patientId;
  successMessage.value = "";

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/patients/${patientId}/restore`,
      {
        method: "POST",
        headers: {
          Accept: "application/json",
        },
      }
    );

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      errorMessage.value =
        data.message || "Não foi possível restaurar o paciente.";
      return;
    }

    patients.value = patients.value.filter(
      (patient) => patient.id !== patientId
    );

    successMessage.value = "Paciente restaurado com sucesso!";
  } catch (error) {
    console.error("Erro ao restaurar paciente:", error);

    errorMessage.value = "Não foi possível conectar à API. Tente novamente.";
  } finally {
    restoringPatientId.value = null;
  }
}

async function loadPatients() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/patients/trashed`,
      {
        headers: {
          Accept: "application/json",
        },
      }
    );

    if (!response.ok) {
      throw new Error("Não foi possível carregar os pacientes.");
    }

    const data = await response.json();

    // O endpoint atual retorna uma lista. O segundo formato
    // também permite compatibilidade com uma futura paginação da API.
    patients.value = Array.isArray(data) ? data : data.data ?? [];
  } catch (error) {
    console.error("Erro ao carregar pacientes:", error);
    errorMessage.value =
      "Não foi possível carregar os pacientes. Tente novamente.";
  } finally {
    isLoading.value = false;
  }
}

const filteredPatients = computed(() => {
  const term = search.value.trim().toLowerCase();

  if (!term) return patients.value;

  const normalizedTerm = term.replace(/\D/g, "");

  return patients.value.filter((patient) => {
    const matchesName = patient.name.toLowerCase().includes(term);

    const matchesCpf =
      normalizedTerm.length > 0 &&
      patient.cpf.replace(/\D/g, "").includes(normalizedTerm);

    return matchesName || matchesCpf;
  });
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredPatients.value.length / itemsPerPage));
});

const paginatedPatients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;

  return filteredPatients.value.slice(start, start + itemsPerPage);
});

const firstItem = computed(() => {
  if (filteredPatients.value.length === 0) return 0;

  return (currentPage.value - 1) * itemsPerPage + 1;
});

const lastItem = computed(() => {
  return Math.min(
    currentPage.value * itemsPerPage,
    filteredPatients.value.length
  );
});

function formatCpf(value: string): string {
  const numbers = value.replace(/\D/g, "");

  return numbers.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, "$1.$2.$3-$4");
}

function formatCellphone(value: string): string {
  const numbers = value.replace(/\D/g, "");

  if (numbers.length === 11) {
    return numbers.replace(/^(\d{2})(\d{5})(\d{4})$/, "($1) $2-$3");
  }

  return value;
}

function changePage(page: number) {
  if (page < 1 || page > totalPages.value) return;

  currentPage.value = page;
}

function formatDate(value: string): string {
  if (!value) return "-";

  const [year, month, day] = value.substring(0, 10).split("-");

  if (!year || !month || !day) return "-";

  return `${day}/${month}/${year}`;
}

function clearSearch() {
  search.value = "";
  currentPage.value = 1;
}

onMounted(loadPatients);
</script>

<template>
  <div class="app-layout">
    <aside class="app-layout__sidebar" aria-label="Menu principal">
      <!-- Vamos implementar o menu lateral na próxima etapa. -->
    </aside>

    <main class="app-layout__content">
      <b-breadcrumb class="app-layout__breadcrumb">
        <b-breadcrumb-item to="/">
          <b-icon icon="house-door-fill" />
          Pacientes
        </b-breadcrumb-item>

        <b-breadcrumb-item active> Excluídos </b-breadcrumb-item>
      </b-breadcrumb>

      <section class="patients-list">
        <div class="patients-list__header">
          <div>
            <h1 class="patients-list__title">Lixeira</h1>
            <p class="patients-list__subtitle">
              Gerencie os pacientes excluídos
            </p>
          </div>
        </div>

        <div class="patients-list__toolbar">
          <div class="patients-list__search">
            <span class="patients-list__search-icon" aria-hidden="true">
              ⌕
            </span>

            <input
              v-model="search"
              type="text"
              placeholder="Buscar por nome ou CPF"
              aria-label="Buscar pacientes por nome ou CPF"
              @input="currentPage = 1"
            />

            <button
              v-if="search"
              type="button"
              class="patients-list__clear-search"
              aria-label="Limpar busca"
              @click="clearSearch"
            >
              ×
            </button>
          </div>
        </div>

        <div v-if="isLoading" class="patients-list__state">
          <b-spinner variant="primary" label="Carregando pacientes" />
          <span>Carregando pacientes...</span>
        </div>

        <div
          v-else-if="errorMessage"
          class="patients-list__state patients-list__state--error"
        >
          <p>{{ errorMessage }}</p>
          <b-button variant="primary" @click="loadPatients">
            Tentar novamente
          </b-button>
        </div>

        <div
          v-else-if="filteredPatients.length === 0"
          class="patients-list__state"
        >
          <p>
            {{
              search
                ? "Nenhum paciente encontrado para essa busca."
                : "Nenhum paciente excluído."
            }}
          </p>

          <router-link v-if="!search" to="/" class="patients-list__empty-link">
            voltar
          </router-link>
        </div>

        <template v-else>
          <b-alert
            v-if="successMessage"
            show
            variant="success"
            dismissible
            @dismissed="successMessage = ''"
          >
            {{ successMessage }}
          </b-alert>
          <div class="patients-list__table-wrapper">
            <table class="patients-list__table">
              <thead>
                <tr>
                  <th>Nome completo</th>
                  <th>CPF</th>
                  <th>Celular</th>
                  <th>Data de nascimento</th>
                  <th class="patients-list__actions-heading">Ações</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="patient in paginatedPatients" :key="patient.id">
                  <td class="patients-list__name">
                    {{ patient.name }}
                  </td>

                  <td>{{ formatCpf(patient.cpf) }}</td>
                  <td>{{ formatCellphone(patient.cellphone) }}</td>
                  <td>{{ formatDate(patient.birth_date) }}</td>

                  <td class="patients-list__actions">
                    <b-button
                      variant="outline-primary"
                      size="sm"
                      :disabled="restoringPatientId !== null"
                      @click="restorePatient(patient.id)"
                    >
                      <b-spinner
                        v-if="restoringPatientId === patient.id"
                        small
                        class="mr-1"
                      />
                      {{
                        restoringPatientId === patient.id
                          ? "Restaurando..."
                          : "Restaurar"
                      }}
                    </b-button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="patients-list__footer">
            <span class="patients-list__counter">
              Mostrando {{ firstItem }}–{{ lastItem }} de
              {{ filteredPatients.length }} pacientes
            </span>

            <div class="patients-list__pagination">
              <button
                type="button"
                :disabled="currentPage === 1"
                aria-label="Página anterior"
                @click="changePage(currentPage - 1)"
              >
                ‹
              </button>

              <button
                v-for="page in totalPages"
                :key="page"
                type="button"
                :class="{ 'is-active': currentPage === page }"
                :aria-current="currentPage === page ? 'page' : undefined"
                @click="changePage(page)"
              >
                {{ page }}
              </button>

              <button
                type="button"
                :disabled="currentPage === totalPages"
                aria-label="Próxima página"
                @click="changePage(currentPage + 1)"
              >
                ›
              </button>
            </div>
          </div>
        </template>
      </section>
    </main>
  </div>
</template>
