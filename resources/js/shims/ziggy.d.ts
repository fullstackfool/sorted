import type {
    Config,
    RouteParamsWithQueryOverload,
    RouteParam,
    Router,
    Route,
    Routable,
    QueryParams,
    RouteParams,
} from 'ziggy-js';
import type { RouteName } from '~/shims/ziggy-route-names';

declare function route(
    name?: undefined,
    params?: RouteParamsWithQueryOverload | RouteParam,
    absolute?: boolean,
    config?: Config,
): Router;
declare function route(
    name: RouteName,
    params?: RouteParamsWithQueryOverload | RouteParam,
    absolute?: boolean,
    config?: Config,
): string;

export {
    Config,
    RouteParamsWithQueryOverload,
    RouteParam,
    Router,
    Route,
    Routable,
    QueryParams,
    RouteParams,
};
export default route;
