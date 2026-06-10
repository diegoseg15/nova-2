<?php

function isContextLocked(): bool
{
    return !empty($_SESSION['context_locked']);
}

function lockContext(): void
{
    $_SESSION['context_locked'] = true;
}

function unlockContext(): void
{
    unset($_SESSION['context_locked']);
    unset($_SESSION['study_group_id']);
    unset($_SESSION['period_id']);
    unset($_SESSION['selected_group_id_temp']);
}

function setContext(int $institutionId, int $studyGroupId, int $periodId): void
{
    $_SESSION['institution_id'] = $institutionId;
    $_SESSION['study_group_id'] = $studyGroupId;
    $_SESSION['period_id'] = $periodId;

    lockContext();

    unset($_SESSION['selected_group_id_temp']);
}

function clearContext(): void
{
    unlockContext();
}

function hasActiveContext(): bool
{
    return !empty($_SESSION['study_group_id']) && !empty($_SESSION['period_id']);
}

function requireContext(): void
{
    if (!hasActiveContext()) {
        redirect_to('select_context.php');
    }
}

function currentInstitutionId(): ?int
{
    return isset($_SESSION['institution_id']) ? (int) $_SESSION['institution_id'] : null;
}

function currentStudyGroupId(): ?int
{
    return isset($_SESSION['study_group_id']) ? (int) $_SESSION['study_group_id'] : null;
}

function currentPeriodId(): ?int
{
    return isset($_SESSION['period_id']) ? (int) $_SESSION['period_id'] : null;
}

function selectedGroupTempId(): ?int
{
    return isset($_SESSION['selected_group_id_temp'])
        ? (int) $_SESSION['selected_group_id_temp']
        : null;
}
