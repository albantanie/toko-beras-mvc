import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Profil',
        href: '/settings/profile',
    },
];

type ProfileForm = {
    name: string;
    username: string;
    phone_number: string;
    address: string;
    email: string;
};

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm<Required<ProfileForm>>(
        undefined,
        {
            name: auth.user.name,
            username: auth.user.username,
            phone_number: auth.user.phone_number,
            address: auth.user.address,
            email: auth.user.email,
        }
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan Profil" />
            
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Informasi Profil" description="Perbarui nama, username, nomor HP, alamat, dan email Anda." />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Nama lengkap"
                            />

                            <InputError className="mt-2" message={errors.name && `Nama wajib diisi.`} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="username">Username</Label>

                            <Input
                                id="username"
                                className="mt-1 block w-full"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="Username"
                            />

                            <InputError className="mt-2" message={errors.username && `Username wajib diisi.`} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone_number">Nomor HP</Label>

                            <Input
                                id="phone_number"
                                className="mt-1 block w-full"
                                value={data.phone_number}
                                onChange={(e) => setData('phone_number', e.target.value)}
                                required
                                autoComplete="tel"
                                placeholder="Nomor HP"
                            />

                            <InputError className="mt-2" message={errors.phone_number && `Nomor HP wajib diisi.`} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>

                            <Input
                                id="address"
                                className="mt-1 block w-full"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                required
                                autoComplete="street-address"
                                placeholder="Alamat lengkap"
                            />

                            <InputError className="mt-2" message={errors.address && `Alamat wajib diisi.`} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Alamat Email</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="Alamat email"
                            />

                            <InputError className="mt-2" message={errors.email && `Alamat email wajib diisi.`} />
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="-mt-4 text-sm text-muted-foreground">
                                    Alamat email Anda belum terverifikasi.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                    >
                                        Klik di sini untuk mengirim ulang email verifikasi.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        Link verifikasi baru telah dikirim ke email Anda.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Simpan</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Tersimpan</p>
                            </Transition>
                        </div>
                    </form>

                    <DeleteUser />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
