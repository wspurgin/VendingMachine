require 'rails_helper'

RSpec.describe User, type: :model do

  # validate presence
  it { should validate_presence_of :email }
  it { should validate_presence_of :name }
  it { should validate_presence_of :smu_id }
  it { should validate_presence_of :group }

  # validate uniqueness
  subject { FactoryGirl.build(:user) }
  it { should validate_uniqueness_of :smu_id }

end
