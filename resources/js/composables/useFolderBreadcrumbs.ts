import { setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Crumb } from '@/types';

/**
 * Đưa đường dẫn thư mục (từ server) lên thanh breadcrumb của layout.
 */
export function useFolderBreadcrumbs(
    crumbs: () => Crumb[],
    extra: () => BreadcrumbItem[] = () => [],
): void {
    watchEffect(() => {
        const items: BreadcrumbItem[] = crumbs().map((crumb) => ({
            title: crumb.name,
            href:
                crumb.kind === 'root'
                    ? dashboard()
                    : FolderController.show(crumb.id),
        }));

        setLayoutProps({ breadcrumbs: [...items, ...extra()] });
    });
}
