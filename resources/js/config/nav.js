export const ROLES = {
  ADMIN: 'admin',
  GESTOR: 'gestor',
  SUPER_ADMIN: 'super_admin',
  PROFESSOR: 'professor',
};

export const NAV_ITEMS = [
  { key: 'schools', label: 'Escolas', route: 'dashboard', rolesAny: [ROLES.ADMIN, ROLES.GESTOR, ROLES.SUPER_ADMIN] },
  { key: 'disciplines', label: 'Disciplinas', route: 'disciplines.index', rolesAny: [ROLES.ADMIN, ROLES.SUPER_ADMIN] },
  { key: 'teacher_links', label: 'Vínculos', route: 'teacher_links.index', rolesAny: [ROLES.ADMIN, ROLES.GESTOR] },
  { key: 'manager_links', label: 'Gestores', route: 'manager_links.index', rolesAny: [ROLES.ADMIN, ROLES.SUPER_ADMIN] },
  { key: 'reports', label: 'Relatórios', route: 'reports.index', rolesAny: [ROLES.ADMIN, ROLES.GESTOR, ROLES.SUPER_ADMIN] },
  { key: 'tenants', label: 'Integrações SED', route: 'tenants.index', rolesAny: [ROLES.SUPER_ADMIN] },
];

export const hasAnyRole = (userRoles = [], allowed = []) =>
  Array.isArray(userRoles) && allowed.some((r) => userRoles.includes(r));