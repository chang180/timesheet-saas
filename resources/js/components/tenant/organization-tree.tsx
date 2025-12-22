import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChevronDown, ChevronRight, Plus, Pencil, Trash2, ArrowUp, ArrowDown } from 'lucide-react';
import { useState } from 'react';

type Organization = {
    divisions: Array<{
        id: number;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
    }>;
    departments: Array<{
        id: number;
        division_id: number | null;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
    }>;
    teams: Array<{
        id: number;
        division_id: number | null;
        department_id: number | null;
        name: string;
        slug: string;
        description: string | null;
        sort_order: number;
        is_active: boolean;
    }>;
};

interface OrganizationTreeProps {
    organization: Organization;
    onEditDivision: (division: Organization['divisions'][0]) => void;
    onDeleteDivision: (division: Organization['divisions'][0]) => void;
    onCreateDepartment: (divisionId?: number) => void;
    onEditDepartment: (department: Organization['departments'][0]) => void;
    onDeleteDepartment: (department: Organization['departments'][0]) => void;
    onCreateTeam: (departmentId?: number, divisionId?: number) => void;
    onEditTeam: (team: Organization['teams'][0]) => void;
    onDeleteTeam: (team: Organization['teams'][0]) => void;
    onMoveUp: (type: 'division' | 'department' | 'team', id: number, currentOrder: number) => void;
    onMoveDown: (type: 'division' | 'department' | 'team', id: number, currentOrder: number, maxOrder: number) => void;
}

export function OrganizationTree({
    organization,
    onEditDivision,
    onDeleteDivision,
    onCreateDepartment,
    onEditDepartment,
    onDeleteDepartment,
    onCreateTeam,
    onEditTeam,
    onDeleteTeam,
    onMoveUp,
    onMoveDown,
}: OrganizationTreeProps) {
    const [expandedDivisions, setExpandedDivisions] = useState<Set<number>>(new Set());
    const [expandedDepartments, setExpandedDepartments] = useState<Set<number>>(new Set());

    const toggleDivision = (id: number) => {
        const newSet = new Set(expandedDivisions);
        if (newSet.has(id)) {
            newSet.delete(id);
        } else {
            newSet.add(id);
        }
        setExpandedDivisions(newSet);
    };

    const toggleDepartment = (id: number) => {
        const newSet = new Set(expandedDepartments);
        if (newSet.has(id)) {
            newSet.delete(id);
        } else {
            newSet.add(id);
        }
        setExpandedDepartments(newSet);
    };

    const sortedDivisions = [...organization.divisions].sort((a, b) => a.sort_order - b.sort_order);
    const sortedDepartments = [...organization.departments].sort((a, b) => a.sort_order - b.sort_order);
    const sortedTeams = [...organization.teams].sort((a, b) => a.sort_order - b.sort_order);

    return (
        <div className="space-y-2">
            {sortedDivisions.map((division, divIndex) => {
                const divisionDepartments = sortedDepartments.filter((d) => d.division_id === division.id);
                const isExpanded = expandedDivisions.has(division.id);

                return (
                    <div key={division.id} className="rounded-lg border border-border bg-card">
                        <div className="flex items-center justify-between p-4">
                            <div className="flex items-center gap-2 flex-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => toggleDivision(division.id)}
                                    className="h-8 w-8 p-0"
                                >
                                    {isExpanded ? (
                                        <ChevronDown className="h-4 w-4" />
                                    ) : (
                                        <ChevronRight className="h-4 w-4" />
                                    )}
                                </Button>
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">{division.name}</span>
                                        {!division.is_active && (
                                            <Badge variant="outline" className="text-xs">
                                                已停用
                                            </Badge>
                                        )}
                                    </div>
                                    {division.description && (
                                        <p className="text-sm text-muted-foreground mt-1">
                                            {division.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {divIndex > 0 && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onMoveUp('division', division.id, division.sort_order)}
                                    >
                                        <ArrowUp className="h-4 w-4" />
                                    </Button>
                                )}
                                {divIndex < sortedDivisions.length - 1 && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onMoveDown('division', division.id, division.sort_order, sortedDivisions.length - 1)}
                                    >
                                        <ArrowDown className="h-4 w-4" />
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onCreateDepartment(division.id)}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onEditDivision(division)}
                                >
                                    <Pencil className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onDeleteDivision(division)}
                                    className="text-destructive hover:text-destructive"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        {isExpanded && (
                            <div className="border-t border-border bg-muted/30">
                                {divisionDepartments.length === 0 ? (
                                    <div className="p-4 text-sm text-muted-foreground">
                                        尚無部門
                                    </div>
                                ) : (
                                    divisionDepartments.map((department, deptIndex) => {
                                        const departmentTeams = sortedTeams.filter(
                                            (t) => t.department_id === department.id,
                                        );
                                        const isDeptExpanded = expandedDepartments.has(department.id);

                                        return (
                                            <div key={department.id} className="border-b border-border last:border-b-0">
                                                <div className="flex items-center justify-between p-4 pl-12">
                                                    <div className="flex items-center gap-2 flex-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => toggleDepartment(department.id)}
                                                            className="h-8 w-8 p-0"
                                                            disabled={departmentTeams.length === 0}
                                                        >
                                                            {departmentTeams.length > 0 && (isDeptExpanded ? (
                                                                <ChevronDown className="h-4 w-4" />
                                                            ) : (
                                                                <ChevronRight className="h-4 w-4" />
                                                            ))}
                                                        </Button>
                                                        <div className="flex-1">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium">{department.name}</span>
                                                                {!department.is_active && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        已停用
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            {department.description && (
                                                                <p className="text-sm text-muted-foreground mt-1">
                                                                    {department.description}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {deptIndex > 0 && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => onMoveUp('department', department.id, department.sort_order)}
                                                            >
                                                                <ArrowUp className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        {deptIndex < divisionDepartments.length - 1 && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => onMoveDown('department', department.id, department.sort_order, divisionDepartments.length - 1)}
                                                            >
                                                                <ArrowDown className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => onCreateTeam(department.id, division.id)}
                                                        >
                                                            <Plus className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => onEditDepartment(department)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => onDeleteDepartment(department)}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                {isDeptExpanded && (
                                                    <div className="bg-muted/50">
                                                        {departmentTeams.length === 0 ? (
                                                            <div className="p-4 pl-20 text-sm text-muted-foreground">
                                                                尚無小組
                                                            </div>
                                                        ) : (
                                                            departmentTeams.map((team, teamIndex) => (
                                                                <div
                                                                    key={team.id}
                                                                    className="flex items-center justify-between p-4 pl-20 border-b border-border last:border-b-0"
                                                                >
                                                                    <div className="flex-1">
                                                                        <div className="flex items-center gap-2">
                                                                            <span className="font-medium">{team.name}</span>
                                                                            {!team.is_active && (
                                                                                <Badge variant="outline" className="text-xs">
                                                                                    已停用
                                                                                </Badge>
                                                                            )}
                                                                        </div>
                                                                        {team.description && (
                                                                            <p className="text-sm text-muted-foreground mt-1">
                                                                                {team.description}
                                                                            </p>
                                                                        )}
                                                                    </div>
                                                                    <div className="flex items-center gap-2">
                                                                        {teamIndex > 0 && (
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => onMoveUp('team', team.id, team.sort_order)}
                                                                            >
                                                                                <ArrowUp className="h-4 w-4" />
                                                                            </Button>
                                                                        )}
                                                                        {teamIndex < departmentTeams.length - 1 && (
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => onMoveDown('team', team.id, team.sort_order, departmentTeams.length - 1)}
                                                                            >
                                                                                <ArrowDown className="h-4 w-4" />
                                                                            </Button>
                                                                        )}
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => onEditTeam(team)}
                                                                        >
                                                                            <Pencil className="h-4 w-4" />
                                                                        </Button>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => onDeleteTeam(team)}
                                                                            className="text-destructive hover:text-destructive"
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            ))
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
