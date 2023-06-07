import {createRouter, createWebHistory} from "vue-router"
import Body from '../components/body.vue';
import Default from '../pages/dashboard/defaultPage.vue';

/* Auth */
import login from '../auth/login.vue';
import forgetPassword from '../auth/forget_password.vue';
import resetPassword from '../auth/reset_password.vue';

// Users Management
import Users from '../pages/users/index.vue';
import UserEdit from '../pages/users/edit.vue';
import UserDetail from '../pages/users/details.vue';

// Roles Management
import Roles from '../pages/roles/index.vue';
import RoleAdd from '../pages/roles/create.vue';
import RoleEdit from '../pages/roles/edit.vue';
import RoleDetail from '../pages/roles/details.vue';


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
          }
        },
        {
          path: 'forget_password',
          name: 'Forget Password',
          component: forgetPassword,
          meta: {
            title: 'POJ - Forget Password',
          }
        },
        {
          path: 'reset_password',
          name: 'Reset Password',
          component: resetPassword,
          meta: {
            title: 'POJ - Reset Password',
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
            },
        },
        {
            path: 'users/detail',
            name: 'User Detail',
            component: UserDetail,
            meta: {
                title: 'POJ - User Detail',
            }
        },
        {
            path: 'users/edit',
            name: 'User Edit',
            component: UserEdit,
            meta: {
                title: 'POJ - User Edit',
            }
        },
        {
            path: 'roles',
            name: 'Roles Management',
            component: Roles,
            meta: {
                title: 'POJ - Roles Management',
            }
        },
        {
            path: 'roles/detail',
            name: 'Role Detail',
            component: RoleDetail,
            meta: {
                title: 'POJ - Role Detail',
            }
        },
        {
            path: 'roles/edit',
            name: 'Role Edit',
            component: RoleEdit,
            meta: {
                title: 'POJ - Role Edit',
            }
        },

      ]
    },
]
const router=createRouter({
    history: createWebHistory(),
    routes,
})
router.beforeEach((to, from, next) => {
  if(to.meta.title)
    document.title = to.meta.title;

  const path = ['/auth/login','/auth/register', '/auth/forget_password', '/auth/reset_password'];
  if(path.includes(to.path) || localStorage.getItem('User')){
    return next();
  }
  next('/auth/login');
});
export default router
