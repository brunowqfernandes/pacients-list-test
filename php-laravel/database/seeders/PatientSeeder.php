<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['Ana Clara Martins', '52998224725', '123456789', '1990-03-15', 'F'],
            ['Carlos Eduardo Silva', '11144477735', '234567891', '1985-07-22', 'M'],
            ['Mariana Oliveira Costa', '93541134780', '345678912', '1995-11-08', 'F'],
            ['João Pedro Santos', '12345678909', '456789123', '1988-01-30', 'M'],
            ['Fernanda Almeida', '98765432100', '567891234', '1992-05-17', 'F'],
            ['Rafael Rodrigues', '22233344405', '678912345', '1980-09-12', 'M'],
            ['Juliana Ferreira', '33344455516', '789123456', '1998-12-03', 'F'],
            ['Lucas Pereira', '44455566627', '891234567', '1993-06-25', 'M'],
            ['Camila Gomes', '55566677738', '912345678', '1987-02-14', 'F'],
            ['Gabriel Ribeiro', '66677788849', '102345678', '1991-08-19', 'M'],
            ['Beatriz Carvalho', '77788899950', '112345678', '1996-04-11', 'F'],
            ['Pedro Henrique Lima', '88899900061', '122345678', '1984-10-29', 'M'],
            ['Larissa Souza', '99900011172', '132345678', '1999-01-05', 'F'],
            ['Matheus Costa', '10120230344', '142345678', '1989-07-16', 'M'],
            ['Isabela Martins', '20230340455', '152345678', '1994-03-21', 'F'],
            ['Thiago Almeida', '30340450566', '162345678', '1982-11-09', 'M'],
            ['Amanda Rocha', '40450560677', '172345678', '1997-06-13', 'F'],
            ['Felipe Nascimento', '50560670788', '182345678', '1986-02-27', 'M'],
            ['Letícia Barbosa', '60670780899', '192345678', '1993-09-04', 'F'],
            ['Vinícius Mendes', '70780890900', '202345678', '1990-12-18', 'M'],
            ['Gabriela Dias', '80890901011', '212345678', '1995-05-23', 'F'],
            ['André Luiz Moreira', '90901012122', '222345678', '1983-08-07', 'M'],
            ['Natália Cardoso', '01012123233', '232345678', '1998-10-15', 'F'],
            ['Diego Fernandes', '12123234344', '242345678', '1987-04-02', 'M'],
            ['Sofia Monteiro', '23234345455', '252345678', '2000-01-20', 'F'],
        ];

        foreach ($patients as $index => $data) {
            [$name, $cpf, $rg, $birthDate, $sex] = $data;

            Patient::updateOrCreate(
                ['cpf' => $cpf],
                [
                    'name' => $name,
                    'rg' => $rg,
                    'birth_date' => $birthDate,
                    'biological_sex' => $sex,
                    'cellphone' => sprintf(
                        '1199%07d',
                        $index + 1
                    ),
                    'email' => strtolower(
                        str_replace(' ', '.', $name)
                    ) . '@example.com',
                    'description' => null,
                ]
            );
        }
    }
}
