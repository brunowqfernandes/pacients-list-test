<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    private function patientData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria da Silva',
            'cpf' => '12345678901',
            'rg' => '123456789',
            'birth_date' => '1990-05-15',
            'biological_sex' => 'F',
            'cellphone' => '11987654321',
            'email' => 'maria@example.com',
            'description' => 'Paciente de teste',
        ], $overrides);
    }

    public function test_it_creates_a_patient(): void
    {
        $response = $this->postJson(
            '/api/patients',
            $this->patientData()
        );

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Maria da Silva')
            ->assertJsonPath('cpf', '12345678901')
            ->assertJsonPath('rg', '123456789')
            ->assertJsonPath('cellphone', '11987654321');

        $this->assertDatabaseHas('patients', [
            'cpf' => '12345678901',
            'name' => 'Maria da Silva',
        ]);
    }

    public function test_it_requires_a_cpf(): void
    {
        $data = $this->patientData();
        unset($data['cpf']);

        $this->postJson('/api/patients', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');
    }

    public function test_it_does_not_allow_duplicate_cpf(): void
    {
        Patient::create($this->patientData());

        $data = $this->patientData([
            'name' => 'João da Silva',
            'rg' => '987654321',
            'cellphone' => '11912345678',
        ]);

        $this->postJson('/api/patients', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');
    }

    public function test_it_lists_patients(): void
    {
        Patient::create($this->patientData());

        Patient::create($this->patientData([
            'name' => 'João da Silva',
            'cpf' => '98765432100',
            'rg' => '987654321',
            'cellphone' => '11912345678',
            'email' => 'joao@example.com',
        ]));

        $this->getJson('/api/patients')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_it_excludes_soft_deleted_patients_from_the_list(): void
    {
        $activePatient = Patient::create($this->patientData());

        $deletedPatient = Patient::create($this->patientData([
            'name' => 'João da Silva',
            'cpf' => '98765432100',
            'rg' => '987654321',
            'cellphone' => '11912345678',
        ]));

        $deletedPatient->delete();

        $this->getJson('/api/patients')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $activePatient->id,
            ])
            ->assertJsonMissing([
                'id' => $deletedPatient->id,
            ]);
    }

    public function test_it_soft_deletes_a_patient(): void
    {
        $patient = Patient::create($this->patientData());

        $this->deleteJson("/api/patients/{$patient->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);
    }

    public function test_it_shows_a_single_patient(): void
    {
        $patient = Patient::create($this->patientData());

        $this->getJson("/api/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('id', $patient->id)
            ->assertJsonPath('name', 'Maria da Silva')
            ->assertJsonPath('cpf', '12345678901');
    }

    public function test_it_updates_a_patient(): void
    {
        $patient = Patient::create($this->patientData());

        $this->putJson("/api/patients/{$patient->id}", [
            'name' => 'Maria Souza',
            'cpf' => '12345678901', // Adicione esta linha
            'rg' => '123456789',
            'birth_date' => '1990-05-15',
            'biological_sex' => 'F',
            'cellphone' => '11987654321',
            'email' => 'maria.souza@example.com',
            'description' => 'Dados atualizados',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Maria Souza')
            ->assertJsonPath('email', 'maria.souza@example.com')
            ->assertJsonPath('description', 'Dados atualizados');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Maria Souza',
        ]);
    }

    public function test_it_does_not_allow_changing_a_patient_cpf(): void
    {
        $patient = Patient::create($this->patientData());

        $this->putJson("/api/patients/{$patient->id}", [
            'name' => 'Maria Souza',
            'cpf' => '98765432100',
            'rg' => '123456789',
            'birth_date' => '1990-05-15',
            'biological_sex' => 'F',
            'cellphone' => '11987654321',
            'email' => 'maria.souza@example.com',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'cpf' => '12345678901',
        ]);
    }

    public function test_it_returns_404_for_a_patient_that_does_not_exist(): void
    {
        $this->getJson('/api/patients/999')
            ->assertNotFound();
    }

    public function test_it_restores_a_soft_deleted_patient(): void
    {
        $patient = Patient::create($this->patientData());
        $patient->delete();

        $this->postJson("/api/patients/{$patient->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'deleted_at' => null,
        ]);

        $this->getJson('/api/patients')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $patient->id,
            ]);
    }

    public function test_it_lists_only_soft_deleted_patients(): void
    {
        Patient::create($this->patientData());

        $deletedPatient = Patient::create($this->patientData([
            'name' => 'João da Silva',
            'cpf' => '98765432100',
            'rg' => '987654321',
            'cellphone' => '11912345678',
        ]));

        $deletedPatient->delete();

        $this->getJson('/api/patients/trashed')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $deletedPatient->id,
            ]);
    }

    public function test_it_does_not_allow_reusing_cpf_of_a_soft_deleted_patient(): void
    {
        $patient = Patient::create($this->patientData());
        $patient->delete();

        $data = $this->patientData([
            'name' => 'Outra pessoa',
            'rg' => '987654321',
            'cellphone' => '11912345678',
        ]);

        $this->postJson('/api/patients', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');
    }
}
