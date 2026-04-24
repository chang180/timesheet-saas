import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    Pencil,
    UserMinus,
} from 'lucide-react';

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
    currentUserId: number;
    companyAdminCount: number;
    onEdit: (member: Member) => void;
    onRemove: (member: Member) => void;
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
    currentUserId,
    companyAdminCount,
    onEdit,
    onRemove,
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
                                        {ROLE_LABELS[member.role] ||
                                            member.role}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div className="space-y-1 text-sm">
                                        {member.division && (
                                            <div>
                                                事業群：{member.division.name}
                                            </div>
                                        )}
                                        {member.department && (
                                            <div>
                                                部門：{member.department.name}
                                            </div>
                                        )}
                                        {member.team && (
                                            <div>小組：{member.team.name}</div>
                                        )}
                                        {!member.division &&
                                            !member.department &&
                                            !member.team && (
                                                <span className="text-muted-foreground">
                                                    未指派
                                                </span>
                                            )}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {member.invitation_accepted_at ? (
                                        <Badge
                                            variant="default"
                                            className="bg-emerald-500"
                                        >
                                            已啟用
                                        </Badge>
                                    ) : member.invitation_sent_at ? (
                                        <Badge
                                            variant="outline"
                                            className="border-amber-500 text-amber-700 dark:text-amber-400"
                                        >
                                            待接受邀請
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">未啟用</Badge>
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => onEdit(member)}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                        <MemberRowMenu
                                            member={member}
                                            currentUserId={currentUserId}
                                            companyAdminCount={companyAdminCount}
                                            onRemove={onRemove}
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {pagination.last_page > 1 && (
                <div className="flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">
                        顯示第{' '}
                        {(pagination.current_page - 1) * pagination.per_page +
                            1}{' '}
                        到{' '}
                        {Math.min(
                            pagination.current_page * pagination.per_page,
                            pagination.total,
                        )}{' '}
                        筆，共 {pagination.total} 筆
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                onPageChange(pagination.current_page - 1)
                            }
                            disabled={pagination.current_page === 1}
                        >
                            <ChevronLeft className="h-4 w-4" />
                            上一頁
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                onPageChange(pagination.current_page + 1)
                            }
                            disabled={
                                pagination.current_page === pagination.last_page
                            }
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

function MemberRowMenu({
    member,
    currentUserId,
    companyAdminCount,
    onRemove,
}: {
    member: Member;
    currentUserId: number;
    companyAdminCount: number;
    onRemove: (member: Member) => void;
}) {
    const isSelf = member.id === currentUserId;
    const isLastAdmin =
        member.role === 'company_admin' && companyAdminCount <= 1;
    const removeDisabled = isSelf || isLastAdmin;
    const removeTooltip = isSelf
        ? '無法移出自己；若你是公司唯一使用者，請使用「關閉公司」。'
        : isLastAdmin
          ? '至少需保留一位公司管理者，請先指派其他管理者。'
          : undefined;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    aria-label="成員操作選單"
                    data-testid={`member-row-menu-${member.id}`}
                >
                    <MoreHorizontal className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem
                    className="text-red-600 focus:text-red-700"
                    disabled={removeDisabled}
                    onSelect={(e) => {
                        e.preventDefault();
                        if (!removeDisabled) onRemove(member);
                    }}
                    title={removeTooltip}
                >
                    <UserMinus className="mr-2 h-4 w-4" />
                    移出公司
                </DropdownMenuItem>
                {removeTooltip && (
                    <>
                        <DropdownMenuSeparator />
                        <div className="px-2 py-1.5 text-xs text-muted-foreground">
                            {removeTooltip}
                        </div>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
