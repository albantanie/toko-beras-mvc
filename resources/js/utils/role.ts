// resources/js/utils/role.ts

import { User } from '@/types';

/**
 * Mengecek apakah user memiliki role tertentu (case-insensitive, mendukung array roles)
 * @param user User object
 * @param roleName Nama role (string, lowercase)
 * @returns boolean
 */
export function hasRole(user: User, roleName: string): boolean {
    if (!user || !user.roles || !Array.isArray(user.roles)) return false;
    return user.roles.some((role) => role.name && role.name.toLowerCase() === roleName.toLowerCase());
}

/**
 * Mendapatkan role utama user (role pertama)
 * @param user User object
 * @returns string | null
 */
export function getMainRole(user: User): string | null {
    if (!user || !user.roles || user.roles.length === 0) return null;
    return user.roles[0].name;
}
