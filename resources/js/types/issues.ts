export type IssuePriority = 'p1' | 'p2' | 'p3' | 'p4';
export type IssueStatus = 'open' | 'handling' | 'completed';
export type Issue = {
    id: number;
    title: string;
    description: string | null;
    priority: IssuePriority;
    status: IssueStatus;
    reported_at: string;
    reported_at_label: string;
    first_responded_at: string | null;
    first_responded_at_label: string | null;
    elapsed_duration: string;
    resolved_at: string | null;
    resolved_at_label: string | null;
    resolution_summary: string | null;
    cause:
        | 'internal_knowledge_gap'
        | 'customer_knowledge_gap'
        | 'user_error'
        | 'code_defect'
        | 'configuration_error'
        | 'infrastructure'
        | 'external_dependency'
        | 'other'
        | null;
    postmortem_required: boolean | null;
    knowledge_base_recorded: boolean;
    is_trend: boolean;
    completed_at: string | null;
    project: {
        id: number;
        name: string;
        customer_name: string | null;
        first_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
        second_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
        third_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
    };
    team_member_id: number | null;
    assigned_to_name: string | null;
    checklist_items: Array<{
        id: number;
        name: string;
        is_required: boolean;
        marks_issue_resolved: boolean;
        is_completed: boolean;
        is_not_applicable: boolean;
        completed_at: string | null;
        completed_by: string | null;
    }>;
    checklist_progress: {
        completed: number;
        total: number;
        required_completed: number;
        required_total: number;
        all_required_completed: boolean;
    };
    sla: {
        response: SlaMilestone;
        resolution: SlaMilestone;
        needs_attention: boolean;
    };
    postmortem: Postmortem | null;
    activities: Array<{
        id: number;
        action: string;
        description: string;
        created_at: string;
        user: string | null;
        mentions: Array<{ id: number; name: string }>;
        attachment: { name: string; download_url: string } | null;
    }>;
};

export type SlaMilestone = {
    target_minutes: number | null;
    deadline_at: string | null;
    state:
        | 'unavailable'
        | 'on_track'
        | 'at_risk'
        | 'overdue'
        | 'met'
        | 'breached';
    label: string;
    remaining_minutes: number | null;
};

export type TeamMember = { id: number; name: string; email: string | null };

export type PostmortemActionItem = {
    id: number | null;
    title: string;
    owner_team_member_id: number | null;
    owner_name?: string | null;
    due_date: string | null;
    is_completed: boolean;
};

export type Postmortem = {
    root_cause: string;
    impact: string;
    action_items: PostmortemActionItem[];
};

export type IssueSla = {
    response: SlaMilestone;
    resolution: SlaMilestone;
    needs_attention: boolean;
};
