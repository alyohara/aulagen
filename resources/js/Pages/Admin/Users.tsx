import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select } from '@/Components/ui/form';
import { UserRow } from '@/types/models';
import { Head, router } from '@inertiajs/react';

interface Props {
    users: UserRow[];
    roles: { value: string; label: string }[];
    provider: { active: string; label: string };
}

export default function AdminUsers({ users, roles }: Props) {
    const updateUser = (id: number, role: string, isActive: boolean) => {
        router.patch(route('admin.users.update', id), { role, is_active: isActive }, { preserveScroll: true, preserveState: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Usuarios</h1>
                        <p className="text-sm text-slate-500">{users.length} usuarios registrados.</p>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => window.history.back()}>Volver</Button>
                </div>
            }
        >
            <Head title="Usuarios" />

            <div className="mx-auto max-w-6xl py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 text-left text-xs uppercase text-slate-400">
                                        <th className="py-2">Nombre</th>
                                        <th className="py-2">Email</th>
                                        <th className="py-2">Rol</th>
                                        <th className="py-2">Materias</th>
                                        <th className="py-2">Activo</th>
                                        <th className="py-2">Alta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map((user) => (
                                        <tr key={user.id} className="border-b border-slate-100">
                                            <td className="py-2 font-medium text-slate-800">{user.name}</td>
                                            <td className="py-2 text-slate-500">{user.email}</td>
                                            <td className="py-2">
                                                <Select
                                                    className="h-8 w-32 text-xs"
                                                    value={user.role}
                                                    onChange={(e) => updateUser(user.id, e.target.value, user.is_active)}
                                                >
                                                    {roles.map((role) => (
                                                        <option key={role.value} value={role.value}>{role.label}</option>
                                                    ))}
                                                </Select>
                                            </td>
                                            <td className="py-2 text-slate-500">{user.courses ?? 0}</td>
                                            <td className="py-2">
                                                <button onClick={() => updateUser(user.id, user.role, !user.is_active)}>
                                                    <Badge tone={user.is_active ? 'success' : 'muted'}>{user.is_active ? 'Sí' : 'No'}</Badge>
                                                </button>
                                            </td>
                                            <td className="py-2 text-slate-400">{user.created_at}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
