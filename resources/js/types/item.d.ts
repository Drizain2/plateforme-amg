import type { Permission } from './auth'

export interface SidebarItem {
    label: string
    href: string
    path: string
    permission?: Permission
    icon: string
}

export interface SidebarGroup {
    label?: string
    items: SidebarItem[]
}
