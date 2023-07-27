import {createStore} from 'vuex'

// import 'es6-promise/auto';
import layout from './modules/layout';
import menu from './modules/menu';
import bootsrap from "./modules/bootsrap"
import timesheetSchedule from "./modules/timesheetSchedule"
// import todo from './modules/todo';
// import firebase_todo from './modules/firebase_todo';
// import common from './modules/common';
// import { users } from './modules/users';
import authentication from '../helpers/authentication';
import axios from 'axios';

const TOKEN_STORAGE_KEY = 'my_app_token';
const ADMIN_UNIT_KEY = "admin_unit";
const ACTIVE_UNIT_RELATION_KEY = "active_unit_relation";

export default createStore({
  state:{
      langIcon: '',
      langLangauge: '',
      isActive:false,
      token:localStorage.getItem(TOKEN_STORAGE_KEY) || null,
      user: null,
      avatar: '',
      roles: [],
      permissions: [],
      adminUnits: null,
      activeAdminUnit: null
  },
  getters:{
    langIcon: (state)=>{ return state.langIcon},
    langLangauge:(state)=>{return state.langLangauge},
    isAuthenticated(state){
        return state.token != null;
    },
    roles: (state) => {
        return state.roles
    },
    permissions: (state) => {
        return state.permissions
    },
    adminUnits: async (state) => {
        if (state.adminUnits === null) {

            let activeUnit = localStorage.getItem(ACTIVE_UNIT_RELATION_KEY)
            let adminUnits = localStorage.getItem(ADMIN_UNIT_KEY)

            if (adminUnits === null || adminUnits === "") {
                await axios.get('api/v1/admin/admin_unit/my').then(response => {
                    state.adminUnits = response.data.data
                    localStorage.setItem(ADMIN_UNIT_KEY, JSON.stringify(state.adminUnits))
                }).catch(error => {
                    console.error(error);
                });
            } else {
                state.adminUnits = JSON.parse(adminUnits)
            }

            if (activeUnit === null || activeUnit === "") {
                state.activeAdminUnit = state.adminUnits[0]
                localStorage.setItem(ACTIVE_UNIT_RELATION_KEY, JSON.stringify(state.activeAdminUnit))
            } else {
                let parsedActiveUnit = JSON.parse(activeUnit)
                let isExist = false

                state.adminUnits.forEach((val) => {
                    if (val.unit_relation_id === parsedActiveUnit.unit_relation_id) {
                        isExist = true
                    }
                })

                if (!isExist) {
                    parsedActiveUnit = state.adminUnits[0]
                }

                state.activeAdminUnit = parsedActiveUnit
            }

            axios.defaults.headers['X-Unit-Relation-ID'] = state.activeAdminUnit.unit_relation_id
        }
        return state.adminUnits
    }
  },
  mutations: {
      changeLang (state, payload) {
        localStorage.setItem('currentLanguage', payload.id);
        localStorage.setItem('currentLanguageIcon', payload.icon);
        state.langIcon = payload.icon || 'flag-icon-us'
        state.langLangauge = payload.id || 'EN'
        // window.location.reload();
      },
      change(state){
        state.isActive = !state.isActive
      },

      setToken(state, token){
        state.token = token;
        localStorage.setItem(TOKEN_STORAGE_KEY, token);
      },
      setUser(state, user){
          state.user = user;
          localStorage.setItem('USER_STORAGE_KEY', JSON.stringify(user));
      },
      setRoles(state, roles) {
          state.roles = roles;
          localStorage.setItem('USER_ROLES', JSON.stringify(roles));
      },
      setPermissions(state, permissions) {
          state.permissions = permissions;
          localStorage.setItem('USER_PERMISSIONS', JSON.stringify(permissions));
      },
      setAvatar(state, user) {
          state.avatar = user.avatar;
          localStorage.setItem('USER_AVATAR', user.avatar);
      },
      setActiveAdminUnit(state, data) {
          state.activeAdminUnit = data
          localStorage.setItem(ACTIVE_UNIT_RELATION_KEY, JSON.stringify(state.activeAdminUnit))

          axios.defaults.headers['X-Unit-Relation-ID'] = data.unit_relation_id
          window.location.reload()
      },
      clearToken(state){
        state.token = null;
        localStorage.removeItem(TOKEN_STORAGE_KEY);
      },
      clearUser(state){
          state.user = null;
          localStorage.removeItem('USER_STORAGE_KEY');
      },
      clearRoles (state)
      {
          state.roles = null;
          localStorage.removeItem('USER_ROLES');
      },
      clearPermissions (state)
      {
          state.permissions = null;
          localStorage.removeItem('USER_PERMISSIONS');
      },
      clearAvatar(state) {
          state.avatar = '';
          localStorage.removeItem('USER_AVATAR');
      },
      clearAdminUnits(state) {
          state.adminUnits = null
          localStorage.removeItem(ADMIN_UNIT_KEY)
      },
      clearActiveAdminUnits(state) {
          state.activeAdminUnit = null
          localStorage.removeItem(ACTIVE_UNIT_RELATION_KEY)
      }
    },
    actions: {
      setLang ({ commit }, payload) {
        commit('changeLang', payload);

      },
      async login ({ commit }, credentials) {
          try {
            const { token, user } = await authentication.login(credentials);
            commit('setToken', token);
            commit('setUser', user);
            commit('setRoles', user.roles);
            commit('setPermissions', user.permissions);
            commit('setAvatar', user);

          } catch (e) {
            commit('clearToken');
            localStorage.removeItem(TOKEN_STORAGE_KEY);
          }
      },
      async logout({ commit }) {
          try {
              // await authentication.logout();
              commit('clearToken');
              commit('clearUser');
              commit('clearRoles');
              commit('clearPermissions');
              commit('clearAvatar');
              commit('clearAdminUnits');
              commit('clearActiveAdminUnits');
              localStorage.removeItem(TOKEN_STORAGE_KEY);
              localStorage.removeItem('USER_STORAGE_KEY');
              localStorage.removeItem('USER_AVATAR');
              console.log('Logout successfully');
          } catch (e) {
              console.log('Logout error:', e);
          }
      },
    },
    modules: {
      layout,
      menu,
      bootsrap,
      timesheetSchedule
    }
});

