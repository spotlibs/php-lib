<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

/**
 * ValidationServiceProvider
 *
 * @category ServiceProvider
 * @package  ServiceProvider
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ValidationServiceProvider extends ServiceProvider
{
    private array $messages_validation = [
        /*
        |---------------------------------------------------------------------------------------
        | Baris Bahasa untuk Validasi
        |---------------------------------------------------------------------------------------
        |
        | Baris bahasa berikut ini berisi standar pesan kesalahan yang digunakan oleh
        | kelas validasi. Beberapa aturan mempunyai banyak versi seperti aturan 'size'.
        | Jangan ragu untuk mengoptimalkan setiap pesan yang ada di sini.
        |
        */

        'accepted'        => ':attribute harus diterima.',
        'active_url'      => ':attribute bukan URL yang valid.',
        'after'           => ':attribute harus berisi tanggal setelah :date.',
        'after_or_equal'  => ':attribute harus berisi tanggal setelah atau sama dengan :date.',
        'alpha'           => ':attribute hanya boleh berisi huruf.',
        'alpha_dash'      => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
        'alpha_num'       => ':attribute hanya boleh berisi huruf dan angka.',
        'array'           => ':attribute harus berisi sebuah array.',
        'before'          => ':attribute harus berisi tanggal sebelum :date.',
        'before_or_equal' => ':attribute harus berisi tanggal sebelum atau sama dengan :date.',
        'between'         => [
            'numeric' => ':attribute harus bernilai antara :min sampai :max.',
            'file'    => ':attribute harus berukuran antara :min sampai :max kilobita.',
            'string'  => ':attribute harus berisi antara :min sampai :max karakter.',
            'array'   => ':attribute harus memiliki :min sampai :max anggota.',
        ],
        'boolean'        => ':attribute harus bernilai true atau false',
        'confirmed'      => 'Konfirmasi :attribute tidak cocok.',
        'date'           => ':attribute bukan tanggal yang valid.',
        'date_equals'    => ':attribute harus berisi tanggal yang sama dengan :date.',
        'date_format'    => ':attribute tidak cocok dengan format :format.',
        'different'      => ':attribute dan :other harus berbeda.',
        'digits'         => ':attribute harus terdiri dari :digits angka.',
        'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
        'dimensions'     => ':attribute tidak memiliki dimensi gambar yang valid.',
        'distinct'       => ':attribute memiliki nilai yang duplikat.',
        'email'          => ':attribute harus berupa alamat surel yang valid.',
        'ends_with'      => ':attribute harus diakhiri salah satu dari berikut: :values',
        'exists'         => ':attribute yang dipilih tidak valid.',
        'file'           => ':attribute harus berupa sebuah berkas.',
        'filled'         => ':attribute harus memiliki nilai.',
        'gt'             => [
            'numeric' => ':attribute harus bernilai lebih besar dari :value.',
            'file'    => ':attribute harus berukuran lebih besar dari :value kilobita.',
            'string'  => ':attribute harus berisi lebih besar dari :value karakter.',
            'array'   => ':attribute harus memiliki lebih dari :value anggota.',
        ],
        'gte' => [
            'numeric' => ':attribute harus bernilai lebih besar dari atau sama dengan :value.',
            'file'    => ':attribute harus berukuran lebih besar dari atau sama dengan :value kilobita.',
            'string'  => ':attribute harus berisi lebih besar dari atau sama dengan :value karakter.',
            'array'   => ':attribute harus terdiri dari :value anggota atau lebih.',
        ],
        'image'    => ':attribute harus berupa gambar.',
        'in'       => ':attribute yang dipilih tidak valid.',
        'in_array' => ':attribute tidak ada di dalam :other.',
        'integer'  => ':attribute harus berupa bilangan bulat.',
        'ip'       => ':attribute harus berupa alamat IP yang valid.',
        'ipv4'     => ':attribute harus berupa alamat IPv4 yang valid.',
        'ipv6'     => ':attribute harus berupa alamat IPv6 yang valid.',
        'json'     => ':attribute harus berupa JSON string yang valid.',
        'lt'       => [
            'numeric' => ':attribute harus bernilai kurang dari :value.',
            'file'    => ':attribute harus berukuran kurang dari :value kilobita.',
            'string'  => ':attribute harus berisi kurang dari :value karakter.',
            'array'   => ':attribute harus memiliki kurang dari :value anggota.',
        ],
        'lte' => [
            'numeric' => ':attribute harus bernilai kurang dari atau sama dengan :value.',
            'file'    => ':attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
            'string'  => ':attribute harus berisi kurang dari atau sama dengan :value karakter.',
            'array'   => ':attribute harus tidak lebih dari :value anggota.',
        ],
        'max' => [
            'numeric' => ':attribute maksimal bernilai :max.',
            'file'    => ':attribute maksimal berukuran :max kilobita.',
            'string'  => ':attribute maksimal berisi :max karakter.',
            'array'   => ':attribute maksimal terdiri dari :max anggota.',
        ],
        'mimes'     => ':attribute harus berupa berkas berjenis: :values.',
        'mimetypes' => ':attribute harus berupa berkas berjenis: :values.',
        'min'       => [
            'numeric' => ':attribute minimal bernilai :min.',
            'file'    => ':attribute minimal berukuran :min kilobita.',
            'string'  => ':attribute minimal berisi :min karakter.',
            'array'   => ':attribute minimal terdiri dari :min anggota.',
        ],
        'not_in'               => ':attribute yang dipilih tidak valid.',
        'not_regex'            => 'Format :attribute tidak valid.',
        'numeric'              => ':attribute harus berupa angka.',
        'password'             => 'Kata sandi salah.',
        'present'              => ':attribute wajib ada.',
        'regex'                => 'Format :attribute tidak valid.',
        'required'             => ':attribute wajib diisi.',
        'required_if'          => ':attribute wajib diisi bila :other adalah :value.',
        'required_unless'      => ':attribute wajib diisi kecuali :other memiliki nilai :values.',
        'required_with'        => ':attribute wajib diisi bila terdapat :values.',
        'required_with_all'    => ':attribute wajib diisi bila terdapat :values.',
        'required_without'     => ':attribute wajib diisi bila tidak terdapat :values.',
        'required_without_all' => ':attribute wajib diisi bila sama sekali tidak terdapat :values.',
        'same'                 => ':attribute dan :other harus sama.',
        'size'                 => [
            'numeric' => ':attribute harus berukuran :size.',
            'file'    => ':attribute harus berukuran :size kilobyte.',
            'string'  => ':attribute harus berukuran :size karakter.',
            'array'   => ':attribute harus mengandung :size anggota.',
        ],
        'starts_with' => ':attribute harus diawali salah satu dari berikut: :values',
        'string'      => ':attribute harus berupa string.',
        'timezone'    => ':attribute harus berisi zona waktu yang valid.',
        'unique'      => ':attribute sudah ada sebelumnya.',
        'uploaded'    => ':attribute gagal diunggah.',
        'url'         => 'Format :attribute tidak valid.',
        'uuid'        => ':attribute harus merupakan UUID yang valid.',

        /*
        |---------------------------------------------------------------------------------------
        | Baris Bahasa untuk Validasi Kustom
        |---------------------------------------------------------------------------------------
        |
        | Di sini Anda dapat menentukan pesan validasi untuk atribut sesuai keinginan dengan menggunakan
        | konvensi "attribute.rule" dalam penamaan barisnya. Hal ini mempercepat dalam menentukan
        | baris bahasa kustom yang spesifik untuk aturan atribut yang diberikan.
        |
        */

        'custom' => [
            'attribute-name' => [
                'rule-name' => 'custom-message',
            ],
        ],

        /*
        |---------------------------------------------------------------------------------------
        | Kustom Validasi Atribut
        |---------------------------------------------------------------------------------------
        |
        | Baris bahasa berikut digunakan untuk menukar 'placeholder' atribut dengan sesuatu yang
        | lebih mudah dimengerti oleh pembaca seperti "Alamat Surel" daripada "surel" saja.
        | Hal ini membantu kita dalam membuat pesan menjadi lebih ekspresif.
        |
        */

        'attributes' => [
        ],
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'validator',
            function ($app) {
                $validator = $app->make(ValidationFactory::class);
                $validator->resolver(
                    function (Translator $translator, array $data, array $rules, array $messages, array $customAttributes) {
                        $messages = array_merge($messages, $this->messages_validation);
                        return new Validator($translator, $data, $rules, $messages, $customAttributes);
                    }
                );
                return $validator;
            }
        );
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        ValidatorFacade::extend(
            'nik_pattern',
            function ($attribute, $value, $parameters, $validator) {
                return is_string($value) && strlen($value) === 16 && preg_match('/^[0-9]+$/', $value);
            },
            ':attribute harus 16 digit angka.'
        );

        ValidatorFacade::extend(
            'npwp_pattern',
            function ($attribute, $value, $parameters, $validator) {
                return is_string($value) && in_array(strlen($value), [15, 16]) && preg_match('/^[0-9]+$/', $value);
            },
            ':attribute harus 15/16 digit angka.'
        );

        ValidatorFacade::extend(
            'gender_pattern',
            function ($attribute, $value, $parameters, $validator) {
                return is_string($value) && in_array($value, ['L', 'P']);
            },
            ':attribute harus L/P.'
        );

        ValidatorFacade::extend(
            'phone_pattern',
            function ($attribute, $value, $parameters, $validator) {
                return is_string($value) && preg_match('/^[0-9]+$/', $value);
            },
            ':attribute harus digit angka.'
        );

        ValidatorFacade::extend(
            'email_pattern',
            function ($attribute, $value, $parameters, $validator) {
                return is_string($value) && str_contains($value, '@');
            },
            ':attribute format tidak valid.'
        );

        ValidatorFacade::extend(
            'required_but_empty',
            function ($attribute, $value, $parameters, $validator) {
                return array_key_exists($attribute, $validator->getData());
            },
            ':attribute harus ada meskipun kosong.'
        );
    }
}
