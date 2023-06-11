import axios from 'axios';

const setRequestHeaders = (token) => {
    const headers = {
        'Content-Type': 'application/json',
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
};


const get = async (url, token = null, params = {}) => {
    try {
      const response = await axios.get(url, {
        params,
        headers: setRequestHeaders(token),
      });

      return response.data;
    } catch (error) {
      throw new Error(error.message);
    }
};

const post = async (url, data = {}, token = null) => {
    try {
      const response = await axios.post(url, data, {
        headers: setRequestHeaders(token),
      });

      return response.data;
    } catch (error) {
      throw new Error(error.message);
    }
};

const checkAccessPermission = (userPermissions, requiredPermissions) => {
    return requiredPermissions.every((permission) =>
      userPermissions.includes(permission)
    );
};

export const API = {
    get,
    post,
    checkAccessPermission,
};

