import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export function usePermission (){
    const page = usePage();

    const hasRole =(role:string):boolean =>{
return page.props.auth.user.roles.some(r=>r.name === role)
    }

    const isSuperAdmin = computed(()=>hasRole('super_admin'))
    const isAdmin = computed(()=>hasRole('admin'))
    const technicien = computed(()=>hasRole('technicien'))

    return{
        hasRole,
        isSuperAdmin,
        isAdmin,
        technicien
    }

}