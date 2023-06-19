import {createRouter, createWebHistory} from "vue-router"
import Body from '../components/body.vue';
import Default from '../pages/dashboard/defaultPage.vue';
import store from '../store';

/* Auth */
import login from '../auth/login.vue';
import forgetPassword from '../auth/forget_password.vue';
import resetPassword from '../auth/reset_password.vue';

// Users Management
import Users from '../pages/users/index.vue';
import UserEdit from '../pages/users/edit.vue';
import UserDetail from '../pages/users/details.vue';
import UserAdd from '../pages/users/create.vue';

// Roles Management
import Roles from '../pages/roles/index.vue';
import RoleAdd from '../pages/roles/create.vue';
import RoleEdit from '../pages/roles/edit.vue';
import RoleDetail from '../pages/roles/details.vue';

// Permission Management
import Permissions from '../pages/permissions/index.vue';
import PermissionAdd from '../pages/permissions/create.vue';
import PermissionEdit from '../pages/permissions/edit.vue';
import PermissionDetail from '../pages/permissions/details.vue';

// Employee Management
import Employees from '../pages/employees/index.vue';
import EmployeeDetail from '../pages/employees/details.vue';

// General Settings
import GeneralSettings from '../pages/settings/index.vue';

// Work Area Settings
import Cabang from '../pages/cabang/index.vue';


// Profile
import Profile from '../pages/profiles/index.vue';

const routes =[
    {
        path: '/',
        component: Body,

        children: [
          {
            path: '',
            name: 'defaultRoot',
            component: Default,
            meta: {
              title: 'POJ - Dashboard',
              requiresAuth: true,
            }
          },

        ]
    },
    {
      path: '/auth',
      children: [
        {
          path: 'login',
          name: 'Login',
          component: login,
          meta: {
            title: 'POJ - Login',
            requiresAuth: false,
          }
        },
        {
          path: 'forget_password',
          name: 'Forget Password',
          component: forgetPassword,
          meta: {
            title: 'POJ - Forget Password',
            requiresAuth: false,
          }
        },
        {
          path: 'reset_password',
          name: 'Reset Password',
          component: resetPassword,
          meta: {
            title: 'POJ - Reset Password',
            requiresAuth: false,
          }
        }

      ]
    },
    {
      path: '/management',
      component: Body,
      children: [
        {
            path: 'users',
            name: 'Users Management',
            component: Users,
            meta: {
                title: 'POJ - Users Management',
                requiresAuth: true,
            },
        },
        {
              path: 'users/create',
              name: 'User Create',
              component: UserAdd,
              meta: {
                  title: 'POJ - User Create',
                  requiresAuth: true,
              }
        },
        {
            path: 'users/detail/:id',
            name: 'User Detail',
            component: UserDetail,
            meta: {
                title: 'POJ - User Detail',
                requiresAuth: true,
            }
        },
        {
            path: 'users/edit/:id',
            name: 'User Edit',
            component: UserEdit,
            meta: {
                title: 'POJ - User Edit',
                requiresAuth: true,
            }
        },
        {
            path: 'roles',
            name: 'Roles Management',
            component: Roles,
            meta: {
                title: 'POJ - Roles Management',
                requiresAuth: true,
            }
        },
        {
              path: 'roles/create',
              name: 'Role Create',
              component: RoleAdd,
              meta: {
                  title: 'POJ - Role Create',
                  requiresAuth: true,
              }
        },
        {
            path: 'roles/detail/:id',
            name: 'Role Detail',
            component: RoleDetail,
            meta: {
                title: 'POJ - Role Detail',
                requiresAuth: true,
            }
        },
        {
            path: 'roles/edit/:id',
            name: 'Role Edit',
            component: RoleEdit,
            meta: {
                title: 'POJ - Role Edit',
                requiresAuth: true,
            }
        },
        {
          path: 'permissions',
          name: 'Permissions Management',
          component: Permissions,
          meta: {
              title: 'POJ - Permissions Management',
              requiresAuth: true,
          }
        },
        {
          path: 'permissions/create',
          name: 'Permission Create',
          component: PermissionAdd,
          meta: {
              title: 'POJ - Permission Create',
              requiresAuth: true,
          }
        },
        {
          path: 'permissions/detail/:id',
          name: 'Permission Detail',
          component: PermissionDetail,
          meta: {
              title: 'POJ - Permission Detail',
              requiresAuth: true,
          }
        },
        {
          path: 'permissions/edit/:id',
          name: 'Permission Edit',
          component: PermissionEdit,
          meta: {
              title: 'POJ - Permission Edit',
              requiresAuth: true,
          }
        },
        {
          path: 'employees',
          name: 'Employee Management',
          component: Employees,
          meta: {
              title: 'POJ - Employee Management',
              requiresAuth: true,
          }
        },
        {
          path: 'employees/detail/:id',
          name: 'Employee Detail',
          component: EmployeeDetail,
          meta: {
              title: 'POJ - Employee Detail',
              requiresAuth: true,
          }
        },
      ]
    },
    {
        path: '/general-settings',
        component: Body,
        children: [
            {
                path: '',
                name: 'General Settings',
                component: GeneralSettings,
                meta: {
                    title: 'POJ - General Settings',
                    requiresAuth: true,
                },
            },
        ]
    },
    {
        path: '/cabang',
        component: Body,
        children: [
            {
                path: '',
                name: 'Cabang',
                component: Cabang,
                meta: {
                    title: 'POJ - Cabang',
                    requiresAuth: true,
                },
            },
        ]
    },
    {
        path: '/profile',
        component: Body,
        children: [
            {
                path: '',
                name: 'Profile',
                component: Profile,
                meta: {
                    title: 'POJ - Profile',
                    requiresAuth: true,
                }
            }
        ]
    }
]
const router=createRouter({
    history: createWebHistory(),
    routes,
})


router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
      if (!store.getters.isAuthenticated) {
        next('/auth/login');
      } else {
        next();
      }
    } else if (!to.meta.requiresAuth && store.getters.isAuthenticated) {
        next({ name: 'defaultRoot' });
    } else {
      next();
    }
});
export default router
