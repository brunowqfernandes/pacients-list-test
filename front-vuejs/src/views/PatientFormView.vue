<script setup lang="ts">
import { reactive, ref, computed, onMounted, getCurrentInstance } from "vue";

import type Vue from "vue";

const instance = getCurrentInstance();
const vm = instance?.proxy as Vue | undefined;

const patientId = computed(() => {
  return vm?.$route.params.id as string | undefined;
});

const isEditing = computed(() => Boolean(patientId.value));
const isLoading = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const isSaving = ref(false);

const patient = reactive({
  name: "",
  cpf: "",
  rg: "",
  birth_date: "",
  biological_sex: "",
  cellphone: "",
  email: "",
  description: "",
});

const errors = reactive({
  name: false,
  cpf: false,
  rg: false,
  birth_date: false,
  biological_sex: false,
  cellphone: false,
});

const toast = reactive({
  visible: false,
  message: "",
  variant: "danger" as "danger" | "success",
  id: 0,
});

let toastTimeout: ReturnType<typeof setTimeout> | undefined;

function allowOnlyNumbers(event: KeyboardEvent) {
  // Permite atalhos como Ctrl+C, Ctrl+V e Ctrl+A.
  if (
    event.ctrlKey ||
    event.metaKey ||
    event.key === "Backspace" ||
    event.key === "Delete" ||
    event.key === "Tab" ||
    event.key.startsWith("Arrow") ||
    event.key === "Home" ||
    event.key === "End"
  ) {
    return;
  }

  if (!/^\d$/.test(event.key)) {
    event.preventDefault();
  }
}

function allowRgCharacters(event: KeyboardEvent) {
  if (
    event.ctrlKey ||
    event.metaKey ||
    event.key === "Backspace" ||
    event.key === "Delete" ||
    event.key === "Tab" ||
    event.key.startsWith("Arrow") ||
    event.key === "Home" ||
    event.key === "End"
  ) {
    return;
  }

  const input = event.target as HTMLInputElement;
  const cursorPosition = input.selectionStart ?? input.value.length;
  const isLastPosition = cursorPosition >= input.value.length;

  // Números são permitidos em qualquer posição.
  if (/^\d$/.test(event.key)) {
    return;
  }

  // X só pode ser digitado no final, uma única vez.
  if (
    /^[xX]$/.test(event.key) &&
    isLastPosition &&
    input.value.replace(/\D/g, "").length >= 8 &&
    !/[xX]/.test(input.value)
  ) {
    return;
  }

  event.preventDefault();
}

function formatCpf(value: string): string {
  const numbers = value.replace(/\D/g, "").slice(0, 11);

  return numbers
    .replace(/^(\d{3})(\d)/, "$1.$2")
    .replace(/^(\d{3})\.(\d{3})(\d)/, "$1.$2.$3")
    .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, "$1.$2.$3-$4");
}

function formatRg(value: string): string {
  // Remove tudo que não seja número ou X.
  const chars = value.toUpperCase().replace(/[^0-9X]/g, "");

  // Os primeiros 8 caracteres só podem ser números.
  const body = chars.slice(0, 8).replace(/X/g, "");

  // O X só é permitido como último dígito.
  const last = chars
    .slice(8)
    .replace(/[^0-9X]/g, "")
    .slice(0, 1);

  const numbers = body + last;

  let formatted = numbers.slice(0, 2);

  if (numbers.length > 2) {
    formatted += `.${numbers.slice(2, 5)}`;
  }

  if (numbers.length > 5) {
    formatted += `.${numbers.slice(5, 8)}`;
  }

  if (numbers.length > 8) {
    formatted += `-${numbers[8]}`;
  }

  return formatted;
}

function formatCellphone(value: string): string {
  const numbers = value.replace(/\D/g, "").slice(0, 11);

  if (numbers.length === 0) return "";
  if (numbers.length <= 2) return `(${numbers}`;

  const ddd = numbers.slice(0, 2);
  const number = numbers.slice(2);

  if (number.length <= 4) {
    return `(${ddd}) ${number}`;
  }

  if (number.length <= 8) {
    return `(${ddd}) ${number.slice(0, 4)}-${number.slice(4)}`;
  }

  return `(${ddd}) ${number.slice(0, 5)}-${number.slice(5)}`;
}

function getPatientPayload() {
  return {
    ...patient,
    cpf: patient.cpf.replace(/\D/g, ""),
    rg: patient.rg.replace(/[^0-9X]/gi, "").toUpperCase(),
    cellphone: patient.cellphone.replace(/\D/g, ""),
  };
}

function showToast(message: string, variant: "danger" | "success") {
  if (toastTimeout) {
    clearTimeout(toastTimeout);
  }

  toast.id++;
  toast.message = message;
  toast.variant = variant;
  toast.visible = true;

  toastTimeout = setTimeout(() => {
    toast.visible = false;
  }, 4000);
}

function validateForm(): boolean {
  errors.name = !patient.name.trim();
  errors.cpf = !patient.cpf.trim();
  errors.rg = !patient.rg.trim();
  errors.birth_date = !patient.birth_date;
  errors.biological_sex = !patient.biological_sex;
  errors.cellphone = !patient.cellphone.trim();

  return !Object.keys(errors).some((key) =>
    Boolean(errors[key as keyof typeof errors])
  );
}

function clearFieldError(field: keyof typeof errors) {
  errors[field] = false;
}

function cancelForm() {
  Object.assign(patient, {
    name: "",
    cpf: "",
    rg: "",
    birth_date: "",
    biological_sex: "",
    cellphone: "",
    email: "",
    description: "",
  });

  Object.keys(errors).forEach((key) => {
    errors[key as keyof typeof errors] = false;
  });
}

async function savePatient() {
  if (isSaving.value) return;

  if (!validateForm()) {
    showToast("Preencha os campos obrigatórios para salvar!", "danger");
    return;
  }

  isSaving.value = true;

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/patients${
        isEditing.value ? `/${patientId.value}` : ""
      }`,
      {
        method: isEditing.value ? "PUT" : "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(getPatientPayload()),
      }
    );

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        const fieldMap: Record<string, keyof typeof errors> = {
          name: "name",
          cpf: "cpf",
          rg: "rg",
          birth_date: "birth_date",
          biological_sex: "biological_sex",
          cellphone: "cellphone",
        };

        Object.keys(data.errors).forEach((field) => {
          const formField = fieldMap[field];

          if (formField) {
            errors[formField] = true;
          }
        });

        showToast(data.message || "Verifique os dados informados.", "danger");
        return;
      }

      showToast(
        data.message || "Não foi possível salvar o paciente.",
        "danger"
      );
      return;
    }

    showToast("Paciente criado/editado com sucesso", "success");

    if (!isEditing.value) {
      cancelForm();
      vm?.$router.push("/");
    } else {
      await loadPatient();
    }
  } catch (error) {
    console.error("Erro ao cadastrar paciente:", error);

    showToast("Não foi possível conectar à API. Tente novamente.", "danger");
  } finally {
    isSaving.value = false;
  }
}

async function deletePatient() {
  if (!patientId.value || isDeleting.value) return;

  isDeleting.value = true;

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/patients/${patientId.value}`,
      {
        method: "DELETE",
        headers: {
          Accept: "application/json",
        },
      }
    );

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      showToast(
        data.message || "Não foi possível excluir o paciente.",
        "danger"
      );
      return;
    }

    showToast("Paciente excluído com sucesso", "success");
    showDeleteModal.value = false;

    await vm?.$router.push("/");
  } catch (error) {
    console.error("Erro ao excluir paciente:", error);

    showToast("Não foi possível conectar à API. Tente novamente.", "danger");
  } finally {
    isDeleting.value = false;
  }
}

async function loadPatient() {
  if (!patientId.value) return;

  isLoading.value = true;

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/patients/${patientId.value}`,
      {
        headers: {
          Accept: "application/json",
        },
      }
    );

    if (!response.ok) {
      if (response.status === 404) {
        showToast("Paciente não encontrado.", "danger");
        vm?.$router.push("/");
        return;
      }

      throw new Error("Não foi possível carregar o paciente.");
    }

    const data = await response.json();

    Object.assign(patient, {
      name: data.name ?? "",
      cpf: formatCpf(data.cpf ?? ""),
      rg: formatRg(data.rg ?? ""),
      birth_date: data.birth_date?.substring(0, 10) ?? "",
      biological_sex: data.biological_sex ?? "",
      cellphone: formatCellphone(data.cellphone ?? ""),
      email: data.email ?? "",
      description: data.description ?? "",
    });
  } catch (error) {
    console.error("Erro ao carregar paciente:", error);
    showToast("Não foi possível carregar os dados do paciente.", "danger");
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadPatient);
</script>

<template>
  <div class="app-layout">
    <transition name="toast">
      <div
        v-if="toast.visible"
        :key="toast.id"
        class="app-toast"
        :class="`app-toast--${toast.variant}`"
        role="alert"
        aria-live="polite"
      >
        <div class="app-toast__content">
          <span class="app-toast__icon" aria-hidden="true">
            {{ toast.variant === "success" ? "✓" : "!" }}
          </span>

          <span class="app-toast__message">
            {{ toast.message }}
          </span>

          <button
            type="button"
            class="app-toast__close"
            aria-label="Fechar notificação"
            @click="toast.visible = false"
          >
            ×
          </button>
        </div>

        <div class="app-toast__progress">
          <div class="app-toast__progress-bar" :key="toast.id"></div>
        </div>
      </div>
    </transition>

    <aside class="app-layout__sidebar" aria-label="Menu principal"></aside>

    <main class="app-layout__content">
      <b-breadcrumb class="app-layout__breadcrumb">
        <b-breadcrumb-item to="/">
          <b-icon icon="house-door-fill" />
          Pacientes
        </b-breadcrumb-item>

        <b-breadcrumb-item active>
          {{ isEditing ? "Editar paciente" : "Novo paciente" }}
        </b-breadcrumb-item>
      </b-breadcrumb>

      <section class="patient-form">
        <div v-if="isLoading" class="patient-form__loading">
          <b-spinner variant="primary" label="Carregando paciente" />

          <span>Carregando dados do paciente...</span>
        </div>

        <template v-else>
          <h1 class="patient-form__title">
            {{ isEditing ? "Editar paciente" : "Cadastro de novo paciente" }}
          </h1>

          <div class="patient-form__divider"></div>

          <form
            class="patient-form__body"
            novalidate
            @submit.prevent="savePatient"
          >
            <div class="patient-form__grid patient-form__grid--identity">
              <!-- Nome completo -->
              <div class="patient-form__field">
                <label for="patient-name">Nome completo</label>

                <b-form-input
                  id="patient-name"
                  v-model="patient.name"
                  placeholder="Insira o nome"
                  :state="errors.name ? false : null"
                  @input="clearFieldError('name')"
                />

                <div v-if="errors.name" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>

              <!-- CPF -->
              <div class="patient-form__field">
                <label for="patient-cpf">CPF</label>

                <b-form-input
                  id="patient-cpf"
                  v-model="patient.cpf"
                  inputmode="numeric"
                  maxlength="14"
                  placeholder="000.000.000-00"
                  :state="errors.cpf ? false : null"
                  @input="patient.cpf = formatCpf(patient.cpf)"
                  @keypress="allowOnlyNumbers"
                  :disabled="isEditing"
                />

                <div v-if="errors.cpf" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>

              <!-- RG -->
              <div class="patient-form__field">
                <label for="patient-rg">RG</label>

                <b-form-input
                  id="patient-rg"
                  v-model="patient.rg"
                  maxlength="12"
                  placeholder="00.000.000-0"
                  :state="errors.rg ? false : null"
                  @input="patient.rg = formatRg(patient.rg)"
                  @keypress="allowRgCharacters"
                />

                <div v-if="errors.rg" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>
            </div>

            <div class="patient-form__grid patient-form__grid--details">
              <!-- Data de nascimento -->
              <div class="patient-form__field">
                <label for="patient-birth-date">Data de nascimento</label>

                <b-form-input
                  id="patient-birth-date"
                  v-model="patient.birth_date"
                  type="date"
                  :state="errors.birth_date ? false : null"
                  @input="clearFieldError('birth_date')"
                />

                <div v-if="errors.birth_date" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>

              <!-- Sexo biológico -->
              <div class="patient-form__field">
                <label for="patient-sex">Sexo biológico</label>

                <b-form-select
                  id="patient-sex"
                  v-model="patient.biological_sex"
                  :options="[
                    { value: '', text: 'Selecionar' },
                    { value: 'M', text: 'Masculino' },
                    { value: 'F', text: 'Feminino' },
                  ]"
                  :state="errors.biological_sex ? false : null"
                  @input="clearFieldError('biological_sex')"
                />

                <div v-if="errors.biological_sex" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>

              <!-- Celular -->
              <div class="patient-form__field">
                <label for="patient-cellphone">Celular</label>

                <b-form-input
                  id="patient-cellphone"
                  v-model="patient.cellphone"
                  inputmode="numeric"
                  maxlength="15"
                  placeholder="(11) 99999-9999"
                  :state="errors.cellphone ? false : null"
                  @input="
                    patient.cellphone = formatCellphone(patient.cellphone)
                  "
                  @keypress="allowOnlyNumbers"
                />

                <div v-if="errors.cellphone" class="patient-form__error">
                  Campo obrigatório
                </div>
              </div>

              <!-- E-mail opcional -->
              <div class="patient-form__field">
                <label for="patient-email">
                  E-mail
                  <span class="patient-form__optional">(Opcional)</span>
                </label>

                <b-form-input
                  id="patient-email"
                  v-model="patient.email"
                  type="email"
                  placeholder="insira o e-mail"
                />
              </div>
            </div>

            <!-- Descrição opcional -->
            <div class="patient-form__field patient-form__field--description">
              <label for="patient-description">
                Descrição
                <span class="patient-form__optional">(Opcional)</span>
              </label>

              <b-form-textarea
                id="patient-description"
                v-model="patient.description"
                placeholder="Descreva o histórico do paciente"
                rows="2"
                max-rows="5"
              />
            </div>

            <!-- Ações -->
            <div class="patient-form__actions">
              <b-button
                type="button"
                variant="link"
                class="patient-form__cancel"
                :disabled="isSaving || isLoading"
                @click="isEditing ? (showDeleteModal = true) : cancelForm()"
              >
                {{ isEditing ? "Excluir paciente" : "Cancelar" }}
              </b-button>

              <b-button
                type="submit"
                variant="primary"
                class="patient-form__submit"
                :disabled="isSaving || isLoading"
              >
                <b-spinner
                  v-if="isSaving"
                  small
                  class="mr-2"
                  label="Salvando"
                />

                {{
                  isSaving
                    ? "Salvando..."
                    : isEditing
                    ? "Atualizar paciente"
                    : "Salvar paciente"
                }}
              </b-button>
            </div>
          </form>
          <b-modal
            v-model="showDeleteModal"
            title="Excluir paciente"
            centered
            hide-footer
            no-close-on-backdrop
          >
            <p>Tem certeza de que deseja excluir este paciente?</p>

            <p class="text-muted">Essa ação removerá o paciente da listagem.</p>

            <div class="d-flex justify-content-end">
              <b-button
                variant="link"
                class="mr-2"
                @click="showDeleteModal = false"
              >
                Cancelar
              </b-button>

              <b-button variant="danger" @click="deletePatient">
                Confirmar exclusão
              </b-button>
            </div>
          </b-modal>
        </template>
      </section>
    </main>
  </div>
</template>
