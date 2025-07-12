import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Icons } from '@/utils/formatters';
import { SweetAlert } from '@/utils/sweetalert';

type RegisterForm = {
    name: string;
    username: string;
    phone_number: string;
    address: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<RegisterForm>>({
        name: '',
        username: '',
        phone_number: '',
        address: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onSuccess: () => {
                SweetAlert.success.custom('Registration Successful!', 'Your account has been created successfully. Welcome to Toko Beras!');
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                }
            },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Daftar - Toko Beras" />

            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <Link href="/" className="flex justify-center items-center space-x-2">
                        <div className="text-3xl">🌾</div>
                        <div>
                            <div className="text-2xl font-bold text-green-600">Toko Beras</div>
                            <div className="text-sm text-gray-500 text-center">Beras Berkualitas</div>
                        </div>
                    </Link>
                    <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">
                        Daftar Akun Baru
                    </h2>
                    <p className="mt-2 text-center text-sm text-gray-600">
                        Atau{' '}
                        <Link
                            href={route('login')}
                            className="font-medium text-green-600 hover:text-green-500"
                        >
                            masuk ke akun yang sudah ada
                        </Link>
                    </p>
                </div>

                <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
                        <form className="space-y-6" onSubmit={submit}>
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                    Nama Lengkap
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        autoComplete="name"
                                        required
                                        autoFocus
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan nama lengkap Anda"
                                    />
                                    <Icons.user className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                </div>
                                {errors.name && (
                                    <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="username" className="block text-sm font-medium text-gray-700">
                                    Username
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="username"
                                        name="username"
                                        type="text"
                                        autoComplete="username"
                                        required
                                        value={data.username}
                                        onChange={(e) => setData('username', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan username unik"
                                    />
                                    <Icons.user className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                </div>
                                {errors.username && (
                                    <p className="mt-2 text-sm text-red-600">{errors.username}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="phone_number" className="block text-sm font-medium text-gray-700">
                                    Nomor HP
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="phone_number"
                                        name="phone_number"
                                        type="tel"
                                        autoComplete="tel"
                                        required
                                        value={data.phone_number}
                                        onChange={(e) => setData('phone_number', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan nomor HP aktif"
                                    />
                                    <Icons.mail className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                </div>
                                {errors.phone_number && (
                                    <p className="mt-2 text-sm text-red-600">{errors.phone_number}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="address" className="block text-sm font-medium text-gray-700">
                                    Alamat
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="address"
                                        name="address"
                                        type="text"
                                        autoComplete="street-address"
                                        required
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan alamat lengkap Anda"
                                    />
                                    <Icons.mail className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                </div>
                                {errors.address && (
                                    <p className="mt-2 text-sm text-red-600">{errors.address}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Alamat Email
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autoComplete="email"
                                        required
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan email Anda"
                                    />
                                    <Icons.mail className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                </div>
                                {errors.email && (
                                    <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                    Kata Sandi
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="password"
                                        name="password"
                                        type={showPassword ? 'text' : 'password'}
                                        autoComplete="new-password"
                                        required
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Masukkan password Anda"
                                    />
                                    <Icons.lock className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-2.5 h-5 w-5 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    >
                                        {showPassword ? (
                                            <Icons.eyeOff className="h-5 w-5" />
                                        ) : (
                                            <Icons.eye className="h-5 w-5" />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="mt-2 text-sm text-red-600">{errors.password}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">
                                    Konfirmasi Kata Sandi
                                </label>
                                <div className="mt-1 relative">
                                    <input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type={showPasswordConfirmation ? 'text' : 'password'}
                                        autoComplete="new-password"
                                        required
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        disabled={processing}
                                        className="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm disabled:opacity-50 text-gray-900 bg-white"
                                        placeholder="Konfirmasi password Anda"
                                    />
                                    <Icons.lock className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                    <button
                                        type="button"
                                        onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                        className="absolute right-3 top-2.5 h-5 w-5 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    >
                                        {showPasswordConfirmation ? (
                                            <Icons.eyeOff className="h-5 w-5" />
                                        ) : (
                                            <Icons.eye className="h-5 w-5" />
                                        )}
                                    </button>
                                </div>
                                {errors.password_confirmation && (
                                    <p className="mt-2 text-sm text-red-600">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {processing && (
                                        <div className="absolute left-0 inset-y-0 flex items-center pl-3">
                                            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                                        </div>
                                    )}
                                    {processing ? 'Memproses...' : 'Daftar Akun'}
                                </button>
                            </div>
                        </form>

                        <div className="mt-6">
                            <div className="relative">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-300" />
                                </div>
                                <div className="relative flex justify-center text-sm">
                                    <span className="px-2 bg-white text-gray-500">Atau</span>
                                </div>
                            </div>

                            <div className="mt-6 text-center">
                                <p className="text-sm text-gray-600">
                                    Sudah punya akun?{' '}
                                    <Link
                                        href={route('login')}
                                        className="font-medium text-green-600 hover:text-green-500"
                                    >
                                        Masuk sekarang
                                    </Link>
                                </p>
                            </div>
                        </div>

                        {/* Terms */}
                        <div className="mt-6">
                            <p className="text-xs text-gray-500 text-center">
                                Dengan mendaftar, Anda menyetujui{' '}
                                <a href="#" className="text-green-600 hover:text-green-500">
                                    Syarat & Ketentuan
                                </a>{' '}
                                dan{' '}
                                <a href="#" className="text-green-600 hover:text-green-500">
                                    Kebijakan Privasi
                                </a>{' '}
                                kami.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="mt-8 text-center">
                    <p className="text-xs text-gray-500">
                        © 2024 Toko Beras. Semua hak dilindungi.
                    </p>
                </div>
            </div>
        </>
    );
}
