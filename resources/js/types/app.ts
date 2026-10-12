export type FolderKind =
    | 'root'
    | 'truong_vinh'
    | 'company_root'
    | 'company'
    | 'project'
    | 'folder';

export type Crumb = {
    id: number;
    name: string;
    kind: FolderKind;
};

export type DocumentItem = {
    id: number;
    name: string;
    extension: string;
    size: number;
    updatedAt: string | null;
    updatedBy: string | null;
    editable: boolean;
    viewable: boolean;
    folderId: number;
    path: string | null;
    deletedAt: string | null;
};

export type FolderSummary = {
    id: number;
    name: string;
    kind: FolderKind;
    isSystem: boolean;
    companyId: number | null;
    isPinned: boolean;
    parentName: string | null;
};

export type PinnedFolder = {
    id: number;
    name: string;
    parent: string | null;
};

export type Navigation = {
    truongVinhId: number | null;
    companyRootId: number | null;
    pins: PinnedFolder[];
};

export type VersionItem = {
    id: number;
    versionNo: number;
    when: string | null;
    who: string;
    what: string;
    size: number;
    isCurrent: boolean;
    downloadUrl: string;
};
