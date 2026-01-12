import Model from 'flarum/common/Model';

export default class Season extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  startDate = Model.attribute<Date | null>('startDate', Model.transformDate);
  endDate = Model.attribute<Date | null>('endDate', Model.transformDate);
}