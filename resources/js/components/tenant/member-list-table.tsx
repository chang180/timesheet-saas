import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Pencil, ChevronLeft, ChevronRight } from 'lucide-react';
import { Skeleton } from '@/components/ui/skeleton';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    division: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    team: { id: number; name: string } | null;
    last_active_at: string | null;
    invitation_sent_at: string | null;
    invitation_accepted_at: string | null;
};

interface MemberListTableProps {
    members: Member[];
    loading: boolean;
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    onEdit: (member: Member) => void;
    onPageChange: (page: number) => void;
}

const ROLE_LABELS: Record<string, string> = {
    company_admin: '公司管理者',
    division_lead: '事業群主管',
    department_manager: '部門主管',
    team_lead: '小組主管',
    member: '一般成員',
};

export function MemberListTable({
    members,
    loading,
    pagination,
    onEdit,
    onPageChange,
}: MemberListTableProps) {
    if (loading) {
        return (
            <div className="space-y-4">
                <div className="space-y-2">
                    {[1, 2, 3, 4, 5].map((i) => (
                        <Skeleton key={i} className="h-12 w-full" />
                    ))}
                </div>
            </div>
        );
    }

    if (members.length === 0) {
        return (
            <div className="py-12 text-center text-muted-foreground">
                尚無成員資料
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>姓名</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>角色</TableHead>
                            <TableHead>組織層級</TableHead>
                            <TableHead>狀態</TableHead>
                            <TableHead className="text-right">操作</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {members.map((member) => (
                            <TableRow key={member.id}>
                                <TableCell className="font-medium">
                                    {member.name}
                                </TableCell>
                                <TableCell>{member.email}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {ROLE_LABELS[member.role] || member.role}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm space-y-1">
                                        {member.division && (
                                            <div>事業群：{member.division.name}</div>
                                        )}
                                        {member.department && (
                                            <div>部門：{member.department.name}</div>
                                        )}
                                        {member.team && (
                                            <div>小組：{member.team.name}</div>
                                        )}
                                        {!member.division && !member.department && !member.team && (
                                            <span className="text-muted-foreground">未指派</span>
                                        )}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {member.invitation_accepted_at ? (
                                        <Badge variant="default" className="bg-emerald-500">
                                            已啟用
                                        </Badge>
                                    ) : member.invitation_sent_at ? (
                                        <Badge variant="outline" className="border-amber-500 text-amber-700 dark:text-amber-400">
                                            待接受邀請
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">未啟用</Badge>
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onEdit(member)}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {pagination.last_page > 1 && (
                <div className="flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">
                        顯示第 {((pagination.current_page - 1) * pagination.per_page) + 1} 到{' '}
                        {Math.min(pagination.current_page * pagination.per_page, pagination.total)} 筆，共 {pagination.total} 筆
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onPageChange(pagination.current_page - 1)}
                            disabled={pagination.current_page === 1}
                        >
                            <ChevronLeft className="h-4 w-4" />
                            上一頁
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onPageChange(pagination.current_page + 1)}
                            disabled={pagination.current_page === pagination.last_page}
                        >
                            下一頁
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
