import { router } from '@inertiajs/react';
import PlatformLayout from '@/Components/Platform/PlatformLayout';
import { Card, EmptyState } from '@/Components/Platform/ui';
import { UsersIcon } from '@/Components/Platform/Icons';

interface Company {
    id: string;
    name: string;
}

interface Department {
    id: string;
    name: string;
}

interface Role {
    id: string;
    department_id: string;
    label: string;
    rank: number;
}

interface Member {
    user_id: string;
    department_id: string;
    role: 'executive' | 'employee';
    role_id: string | null;
    users: { name: string; email: string };
}

interface AssignRolesPageProps {
    company: Company;
    departments: Department[];
    roles: Role[];
    members: Member[];
    [key: string]: unknown;
}

function MemberRow({ companyId, departmentId, member, roles }: { companyId: string; departmentId: string; member: Member; roles: Role[] }) {
    const update = (field: 'role' | 'role_id', value: string) => {
        router.patch(`/platform/companies/${companyId}/departments/${departmentId}/users/${member.user_id}/role`, {
            role: field === 'role' ? value : member.role,
            role_id: field === 'role_id' ? value : member.role_id ?? '',
        });
    };

    return (
        <li className="flex items-center justify-between gap-3 py-2.5">
            <div className="flex-1">
                <p className="text-sm text-slate-800">{member.users.name}</p>
                <p className="text-xs text-slate-400">{member.users.email}</p>
            </div>
            <select value={member.role} onChange={(e) => update('role', e.target.value)} className="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                <option value="employee">Employee</option>
                <option value="executive">Executive</option>
            </select>
            <select value={member.role_id ?? ''} onChange={(e) => update('role_id', e.target.value)} className="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                {roles.map((r) => (
                    <option key={r.id} value={r.id}>
                        {r.label}
                    </option>
                ))}
            </select>
        </li>
    );
}

export default function AssignRoles({ company, departments, roles, members }: AssignRolesPageProps) {
    const rolesByDepartment = roles.reduce<Record<string, Role[]>>((acc, r) => {
        (acc[r.department_id] ??= []).push(r);
        return acc;
    }, {});

    const membersByDepartment = members.reduce<Record<string, Member[]>>((acc, m) => {
        (acc[m.department_id] ??= []).push(m);
        return acc;
    }, {});

    return (
        <PlatformLayout
            title="Assign Roles"
            description="Every imported or invited employee starts as a plain Employee — adjust who should be an Executive, and which job-level role fits each person."
            company={company}
            maxWidth="max-w-2xl"
        >
            {departments.length === 0 ? (
                <Card>
                    <EmptyState icon={<UsersIcon className="w-10 h-10" />} title="No departments yet" description="Nothing to assign roles in until a department exists." />
                </Card>
            ) : (
                departments.map((dept) => (
                    <Card key={dept.id} title={dept.name} className="mb-4">
                        {(membersByDepartment[dept.id] ?? []).length === 0 ? (
                            <p className="text-xs text-slate-400">No members in this department yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {(membersByDepartment[dept.id] ?? []).map((m) => (
                                    <MemberRow key={m.user_id} companyId={company.id} departmentId={dept.id} member={m} roles={rolesByDepartment[dept.id] ?? []} />
                                ))}
                            </ul>
                        )}
                    </Card>
                ))
            )}
        </PlatformLayout>
    );
}
